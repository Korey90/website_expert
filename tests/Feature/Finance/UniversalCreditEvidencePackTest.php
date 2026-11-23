<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CalculateUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\ClassifyUniversalCreditReportItemAction;
use App\Actions\Finance\CreateUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\CreateUniversalCreditManualEntryAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\LockUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\RequestUniversalCreditEvidencePackAction;
use App\Actions\Finance\RetryUniversalCreditEvidencePackAction;
use App\Actions\Finance\ReviewUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\UpdateUniversalCreditProfileAction;
use App\Jobs\GenerateUniversalCreditEvidencePackJob;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\FinancialAttachment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditEvidencePack;
use App\Models\UniversalCreditEvidencePackItem;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditReportItem;
use App\Models\User;
use App\Services\Finance\UniversalCreditEvidencePackGenerator;
use App\Services\Finance\UniversalCreditEvidenceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Throwable;
use ZipArchive;

class UniversalCreditEvidencePackTest extends TestCase
{
    use RefreshDatabase;

    private const CLEAN_CONTENT = "%PDF-1.4\nclean evidence\n%%EOF";

    private Business $business;

    private User $owner;

    private UniversalCreditAssessmentPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('financial');
        Queue::fake();

        $this->business = Business::create([
            'name' => 'Website Expert NI',
            'slug' => 'website-expert-ni-uc-evidence-pack',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $this->owner->givePermissionTo([
            'view_universal_credit_reporting',
            'manage_universal_credit_reporting',
            'export_universal_credit_pack',
        ]);
        $this->actingAs($this->owner);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->owner)['profile']->update([
            'entity_type' => 'sole_trader',
            'legal_name' => 'Website Expert NI',
            'vat_registered' => false,
        ]);
        app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, ['is_enabled' => true]);
        $this->period = app(CreateUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->business, $this->owner, '2026-11-23', '2026-12-22',
        );
    }

    public function test_pack_is_queued_once_and_contains_verified_reports_manifest_and_clean_evidence(): void
    {
        $clean = $this->manualIncomeWithAttachment('125.00', FinancialAttachment::SCAN_CLEAN, 'receipt.pdf', self::CLEAN_CONTENT);
        $pending = $this->manualIncomeWithAttachment('20.00', FinancialAttachment::SCAN_PENDING, 'pending.pdf', 'pending evidence');
        $this->classify($clean['item'], UniversalCreditReportItem::STATUS_INCLUDED);
        $this->classify($pending['item'], UniversalCreditReportItem::STATUS_EXCLUDED);
        $this->lockPeriod();

        $first = app(RequestUniversalCreditEvidencePackAction::class)->execute($this->period, $this->owner, 'pl');
        $second = app(RequestUniversalCreditEvidencePackAction::class)->execute($this->period, $this->owner, 'en');

        $this->assertTrue($first->is($second));
        $this->assertSame('pl', $first->locale);
        Queue::assertPushed(GenerateUniversalCreditEvidencePackJob::class, 1);

        $this->runJob($first);
        $pack = $first->fresh('items');

        $this->assertSame(UniversalCreditEvidencePack::STATUS_COMPLETED, $pack->status);
        $this->assertSame(1, $pack->attempt_count);
        $this->assertSame(64, strlen((string) $pack->zip_sha256));
        $this->assertTrue($pack->expires_at->greaterThan($pack->generated_at));
        Storage::disk('financial')->assertExists($pack->path);
        $this->assertSame($pack->included_count + $pack->excluded_count, $pack->items->count());
        $this->assertTrue($pack->items->contains(fn (UniversalCreditEvidencePackItem $item): bool => $item->attachment_id === $clean['attachment']->id
                && $item->inclusion_status === UniversalCreditEvidencePackItem::INCLUDED));
        $this->assertTrue($pack->items->contains(fn (UniversalCreditEvidencePackItem $item): bool => $item->attachment_id === $pending['attachment']->id
                && $item->inclusion_status === UniversalCreditEvidencePackItem::EXCLUDED
                && $item->reason === 'scan_pending'));

        $archive = $this->openPack($pack);
        try {
            $names = $this->archiveNames($archive);
            foreach ([
                'README.txt',
                'manifest.sha256.json',
                '01-summary/universal-credit-summary.pdf',
                '01-summary/universal-credit-summary.xlsx',
                '01-summary/universal-credit-summary.csv',
                '01-summary/review-and-declaration.pdf',
                '02-details/income-detail.csv',
                '02-details/expense-detail.csv',
                '02-details/excluded-and-private.csv',
                '02-details/bank-and-cash-reconciliation.xlsx',
                '02-details/missing-evidence-and-warnings.csv',
                '02-details/calculation-policy.pdf',
                '04-audit/source-inventory.csv',
                '04-audit/exclusions.csv',
                '04-audit/generation-metadata.json',
            ] as $expected) {
                $this->assertContains($expected, $names);
            }
            $this->assertTrue(collect($names)->contains(fn (string $name): bool => str_ends_with($name, '-receipt.pdf')));
            $this->assertFalse(collect($names)->contains(fn (string $name): bool => str_ends_with($name, '-pending.pdf')));
            $this->assertFalse(collect($names)->contains(fn (string $name): bool => str_starts_with($name, '/') || str_contains($name, '\\') || in_array('..', explode('/', $name), true)));

            $manifest = json_decode((string) $archive->getFromName('manifest.sha256.json'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame($this->period->review_snapshot_hash, data_get($manifest, 'assessment_period.review_snapshot_hash'));
            $readme = (string) $archive->getFromName('README.txt');
            $this->assertStringContainsString('Hash snapshotu weryfikacji: '.$this->period->review_snapshot_hash, $readme);
            $this->assertStringNotContainsString('Hash snapshotu obliczeń: '.$this->period->review_snapshot_hash, $readme);
            $this->assertTrue(collect($manifest['excluded'])->contains(fn (array $item): bool => $item['attachment_id'] === $pending['attachment']->id && $item['reason'] === 'scan_pending'));
            $this->assertStringNotContainsString(Storage::disk('financial')->path(''), json_encode($manifest, JSON_THROW_ON_ERROR));

            foreach ($manifest['files'] as $file) {
                $contents = $archive->getFromName($file['path']);
                $this->assertIsString($contents, $file['path']);
                $this->assertSame($file['bytes'], strlen($contents), $file['path']);
                $this->assertSame($file['sha256'], hash('sha256', $contents), $file['path']);
            }
        } finally {
            $archive->close();
            @unlink($this->archivePath($pack));
        }

        $itemCount = $pack->items->count();
        $this->runJob($pack);
        $this->assertSame($itemCount, $pack->items()->count());

        $response = $this->get(route('admin.finance.universal-credit.evidence-packs.download', $pack));
        $response->assertOk()
            ->assertHeader('x-universal-credit-pack-sha256', $pack->zip_sha256)
            ->assertHeader('x-universal-credit-snapshot-sha256', $pack->snapshot_hash);
        $this->assertStringContainsString('private', (string) $response->headers->get('cache-control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('cache-control'));

        $storedZip = Storage::disk('financial')->get($pack->path);
        Storage::disk('financial')->put($pack->path, 'tampered zip');
        $this->get(route('admin.finance.universal-credit.evidence-packs.download', $pack))->assertConflict();
        Storage::disk('financial')->put($pack->path, $storedZip);

        $viewer = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $viewer->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $viewer->givePermissionTo('view_universal_credit_reporting');
        $this->actingAs($viewer)
            ->get(route('admin.finance.universal-credit.evidence-packs.download', $pack))
            ->assertForbidden();
    }

    public function test_tampered_evidence_fails_closed_and_a_failed_pack_can_be_retried(): void
    {
        $clean = $this->manualIncomeWithAttachment('75.00', FinancialAttachment::SCAN_CLEAN, 'receipt.pdf', self::CLEAN_CONTENT);
        $this->classify($clean['item'], UniversalCreditReportItem::STATUS_INCLUDED);
        $this->lockPeriod();
        $pack = app(RequestUniversalCreditEvidencePackAction::class)->execute($this->period, $this->owner, 'en');

        Storage::disk('financial')->put($clean['attachment']->path, 'tampered evidence');
        $job = new GenerateUniversalCreditEvidencePackJob($pack->id);
        $failure = null;
        try {
            $job->handle(app(UniversalCreditEvidencePackGenerator::class));
            $this->fail('Tampered evidence must fail the pack build.');
        } catch (Throwable $exception) {
            $failure = $exception;
        }
        $this->assertInstanceOf(DomainException::class, $failure);
        $job->failed($failure);

        $pack = $pack->fresh();
        $this->assertSame(UniversalCreditEvidencePack::STATUS_FAILED, $pack->status);
        $this->assertSame('build_validation_failed', $pack->last_error_code);
        $this->assertSame(0, $pack->items()->count());

        Storage::disk('financial')->put($clean['attachment']->path, self::CLEAN_CONTENT);
        Queue::fake();
        $retried = app(RetryUniversalCreditEvidencePackAction::class)->execute($pack, $this->owner);
        $this->assertSame(UniversalCreditEvidencePack::STATUS_PENDING, $retried->status);
        Queue::assertPushed(GenerateUniversalCreditEvidencePackJob::class, 1);

        $this->runJob($retried);
        $this->assertSame(UniversalCreditEvidencePack::STATUS_COMPLETED, $retried->fresh()->status);
        $this->assertSame(2, $retried->fresh()->attempt_count);
    }

    public function test_structured_payment_evidence_snapshots_attachments_from_the_payment_and_invoice(): void
    {
        $client = Client::create([
            'business_id' => $this->business->id,
            'company_name' => 'Evidence Customer Ltd',
            'status' => 'active',
        ]);
        $invoice = Invoice::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => 'INV-UC-EVIDENCE-1',
            'client_id' => $client->id,
            'created_by' => $this->owner->id,
            'status' => 'paid',
            'currency' => 'GBP',
            'issue_date' => '2026-11-24',
            'tax_point' => '2026-11-24',
            'due_date' => '2026-12-20',
        ]);
        $payment = Payment::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'invoice_id' => $invoice->id,
            'created_by' => $this->owner->id,
            'amount' => '50.00',
            'amount_gbp' => '50.00',
            'currency' => 'GBP',
            'gbp_exchange_rate' => '1.00000000',
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-11-25',
            'method' => 'bank_transfer',
            'status' => 'completed',
            'paid_at' => '2026-11-25',
        ]);
        $invoiceAttachment = $this->attachment($invoice, FinancialAttachment::SCAN_CLEAN, 'issued-invoice.pdf', self::CLEAN_CONTENT);
        $paymentAttachment = $this->attachment($payment, FinancialAttachment::SCAN_PENDING, 'bank-confirmation.pdf', 'pending evidence');
        $item = new UniversalCreditReportItem([
            'business_id' => $this->business->id,
            'source_type' => $payment->getMorphClass(),
            'source_id' => $payment->id,
        ]);

        $evidence = app(UniversalCreditEvidenceService::class)->forItem($item);

        $this->assertSame('structured_record', $evidence['state']);
        $this->assertSame([$invoiceAttachment->id, $paymentAttachment->id], $evidence['attachment_ids']);
        $this->assertSame([$invoiceAttachment->sha256, $paymentAttachment->sha256], $evidence['attachment_hashes']);
        $this->assertSame([FinancialAttachment::SCAN_CLEAN, FinancialAttachment::SCAN_PENDING], $evidence['scan_statuses']);
    }

    public function test_expiry_removes_only_the_private_zip_and_preserves_audit_records(): void
    {
        $this->lockPeriod();
        $pack = app(RequestUniversalCreditEvidencePackAction::class)->execute($this->period, $this->owner, 'en');
        $this->runJob($pack);
        $pack = $pack->fresh();
        $itemCount = $pack->items()->count();

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit passed.')
            ->assertSuccessful();

        DB::table('universal_credit_evidence_packs')->where('id', $pack->id)->update([
            'included_count' => $pack->included_count + 1,
        ]);
        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('universal_credit_evidence_packs.snapshot')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
        DB::table('universal_credit_evidence_packs')->where('id', $pack->id)->update([
            'included_count' => $pack->included_count,
        ]);

        DB::table('universal_credit_evidence_packs')->where('id', $pack->id)->update(['expires_at' => now()->subSecond()]);
        $this->get(route('admin.finance.universal-credit.evidence-packs.download', $pack))->assertGone();
        $this->artisan('finance:cleanup-universal-credit-packs')
            ->expectsOutput('Expired Universal Credit evidence packs cleaned: 1.')
            ->assertSuccessful();

        $pack = $pack->fresh();
        $this->assertSame(UniversalCreditEvidencePack::STATUS_EXPIRED, $pack->status);
        $this->assertNotNull($pack->file_deleted_at);
        Storage::disk('financial')->assertMissing($pack->path);
        $this->assertSame($itemCount, $pack->items()->count());
        $this->assertSame(64, strlen((string) $pack->zip_sha256));

        $this->expectException(DomainException::class);
        $pack->update(['filename' => 'changed.zip']);
    }

    public function test_request_rejects_tampered_review_snapshot_and_tenant_audit_covers_pack_records(): void
    {
        $this->lockPeriod();
        DB::table('universal_credit_assessment_periods')->where('id', $this->period->id)->update([
            'review_snapshot_hash' => str_repeat('0', 64),
        ]);

        try {
            app(RequestUniversalCreditEvidencePackAction::class)->execute($this->period->fresh(), $this->owner, 'en');
            $this->fail('A tampered review snapshot must not be packaged.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.universal_credit.pack.errors.snapshot_integrity'), $exception->getMessage());
        }

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('universal_credit_assessment_periods.review_snapshot')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }

    /** @return array{item: UniversalCreditReportItem, attachment: FinancialAttachment} */
    private function manualIncomeWithAttachment(string $amount, string $scanStatus, string $name, string $contents): array
    {
        $entry = app(CreateUniversalCreditManualEntryAction::class)->execute($this->period, $this->owner, [
            'direction' => UniversalCreditReportItem::DIRECTION_INCOME,
            'event_type' => UniversalCreditManualEntry::EVENT_CASH_RECEIPT,
            'category' => 'goods_and_services',
            'cash_date' => '2026-12-01',
            'original_amount' => $amount,
            'original_currency' => 'GBP',
            'description' => 'Cash receipt with evidence '.$name,
            'evidence_missing_reason' => 'Evidence is attached separately for this test.',
        ]);
        $attachment = $this->attachment($entry, $scanStatus, $name, $contents);

        return ['item' => $entry->reportItems->firstOrFail(), 'attachment' => $attachment];
    }

    private function attachment(object $attachable, string $scanStatus, string $name, string $contents): FinancialAttachment
    {
        $path = $this->business->id.'/'.str(class_basename($attachable))->kebab().'/'.$attachable->id.'/'.$name;
        Storage::disk('financial')->put($path, $contents);

        return FinancialAttachment::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->id,
            'uploaded_by' => $this->owner->id,
            'document_type' => 'other',
            'disk' => 'financial',
            'path' => $path,
            'original_name' => $name,
            'mime_type' => 'application/pdf',
            'size' => strlen($contents),
            'sha256' => hash('sha256', $contents),
            'scan_status' => $scanStatus,
            'scan_requested_at' => now(),
            'scanned_at' => $scanStatus === FinancialAttachment::SCAN_CLEAN ? now() : null,
            'retention_until' => today()->addYears(6),
        ]);
    }

    private function classify(UniversalCreditReportItem $item, string $status): void
    {
        app(ClassifyUniversalCreditReportItemAction::class)->execute(
            $item,
            $this->owner,
            $status,
            'goods_and_services',
            classificationReason: $status === UniversalCreditReportItem::STATUS_EXCLUDED
                ? 'Private or otherwise excluded from this assessment period.'
                : null,
        );
    }

    private function lockPeriod(): void
    {
        $this->period = app(CalculateUniversalCreditAssessmentPeriodAction::class)
            ->execute($this->period->fresh(), $this->owner);
        $this->period = app(ReviewUniversalCreditAssessmentPeriodAction::class)
            ->execute($this->period, $this->owner, true, true);
        $this->period = app(LockUniversalCreditAssessmentPeriodAction::class)
            ->execute($this->period, $this->owner, 'Reviewed and locked for immutable evidence pack generation.');
    }

    private function runJob(UniversalCreditEvidencePack $pack): void
    {
        (new GenerateUniversalCreditEvidencePackJob($pack->id))
            ->handle(app(UniversalCreditEvidencePackGenerator::class));
    }

    private function openPack(UniversalCreditEvidencePack $pack): ZipArchive
    {
        file_put_contents($this->archivePath($pack), Storage::disk($pack->disk)->get($pack->path));
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($this->archivePath($pack)) === true);

        return $archive;
    }

    private function archivePath(UniversalCreditEvidencePack $pack): string
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.'uc-pack-'.$pack->id.'.zip';
    }

    /** @return list<string> */
    private function archiveNames(ZipArchive $archive): array
    {
        $names = [];
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $name = $archive->getNameIndex($index);
            if (is_string($name)) {
                $names[] = $name;
            }
        }

        return $names;
    }
}
