<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateVatReturnReviewAction;
use App\Actions\Finance\GenerateAccountantPackAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\LockVatReturnAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\ReviewVatReturnAction;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\FinancialAttachment;
use App\Models\LedgerAccount;
use App\Models\TaxCode;
use App\Models\User;
use App\Models\VatAdjustment;
use App\Models\VatReturn;
use App\Services\Finance\FinancialAttachmentService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use ZipArchive;

class AccountantPackTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('financial');
        Queue::fake();

        $this->business = Business::create([
            'name' => 'Website Expert NI',
            'slug' => 'website-expert-ni-accountant-pack',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        foreach (['access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance', 'close_finance_periods', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo([
            'access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance',
            'close_finance_periods', 'post_finance',
        ]);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $this->business->accountingProfile()->update([
            'vat_registered' => true,
            'vat_number' => 'GB123456789',
            'vat_scheme' => 'standard',
            'vat_effective_from' => '2020-01-01',
        ]);
        AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => 'FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_locked_return_generates_a_verified_accountant_pack(): void
    {
        [$return, $attachments] = $this->lockedReturnWithAttachments();
        $result = app(GenerateAccountantPackAction::class)->execute($return, $this->user);

        try {
            $this->assertFileExists($result['path']);
            $this->assertSame(hash_file('sha256', $result['path']), $result['sha256']);
            $this->assertSame(filesize($result['path']), $result['bytes']);
            $this->assertStringContainsString($return->number, $result['filename']);

            $zip = new ZipArchive;
            $this->assertTrue($zip->open($result['path']));

            try {
                $cleanPath = 'attachments/'.$attachments['clean']->id.'-clean-evidence.pdf';
                $pendingPath = 'attachments/'.$attachments['pending']->id.'-pending-evidence.pdf';
                $rejectedPath = 'attachments/'.$attachments['rejected']->id.'-rejected-evidence.pdf';
                $expected = [
                    'README.txt',
                    'accountant-pack.xlsx',
                    'attachments-manifest.csv',
                    'manifest.sha256.json',
                    'source-journals.csv',
                    'vat-detail.csv',
                    'vat-return-boxes.csv',
                    'vat-return-summary.pdf',
                    $cleanPath,
                ];

                foreach ($expected as $entry) {
                    $this->assertNotFalse($zip->locateName($entry), "Missing ZIP entry [{$entry}].");
                }

                $this->assertFalse($zip->locateName($pendingPath));
                $this->assertFalse($zip->locateName($rejectedPath));
                $this->assertSame($attachments['clean']->sha256, hash('sha256', $zip->getFromName($cleanPath)));
                $this->assertStringStartsWith('%PDF-', (string) $zip->getFromName('vat-return-summary.pdf'));
                $this->assertStringStartsWith('PK', (string) $zip->getFromName('accountant-pack.xlsx'));

                $attachmentManifest = (string) $zip->getFromName('attachments-manifest.csv');
                $this->assertStringContainsString("'=clean-evidence.pdf", $attachmentManifest);
                $this->assertStringContainsString('included', $attachmentManifest);
                $this->assertStringContainsString('excluded_pending', $attachmentManifest);
                $this->assertStringContainsString('excluded_rejected', $attachmentManifest);
                $this->assertStringNotContainsString($attachments['clean']->path, $attachmentManifest);

                $manifest = json_decode((string) $zip->getFromName('manifest.sha256.json'), true, 512, JSON_THROW_ON_ERROR);
                $this->assertSame('INTERNAL ACCOUNTANT REVIEW / NOT SUBMITTED TO HMRC', $manifest['classification']);
                $this->assertSame($return->snapshot_hash, $manifest['vat_return']['snapshot_hash']);
                $this->assertSame($this->user->id, $manifest['generated_by']['id']);
                $this->assertSame('Website Expert NI', $manifest['business']['name']);

                foreach ($manifest['files'] as $file) {
                    $contents = $zip->getFromName($file['name']);
                    $this->assertNotFalse($contents);
                    $this->assertSame($file['sha256'], hash('sha256', $contents));
                    $this->assertSame($file['bytes'], strlen($contents));
                }

                $textFiles = implode("\n", array_map(
                    fn (string $entry): string => (string) $zip->getFromName($entry),
                    ['README.txt', 'attachments-manifest.csv', 'manifest.sha256.json', 'source-journals.csv', 'vat-detail.csv', 'vat-return-boxes.csv'],
                ));
                $this->assertStringNotContainsString('GB123456789', $textFiles);
                $this->assertStringContainsString('NOT SUBMITTED TO HMRC', $textFiles);

                $xlsxPath = tempnam(sys_get_temp_dir(), 'accountant-pack-');
                $this->assertNotFalse($xlsxPath);
                file_put_contents($xlsxPath, $zip->getFromName('accountant-pack.xlsx'));

                try {
                    $workbook = IOFactory::load($xlsxPath);
                    $this->assertSame(
                        ['Summary', 'VAT Return boxes', 'VAT detail', 'Tax codes', 'Source journals', 'Attachments'],
                        $workbook->getSheetNames(),
                    );
                    $this->assertSame('Website Expert NI', $workbook->getSheetByName('Summary')?->getCell('B2')->getValue());
                    $this->assertSame(20, $workbook->getSheetByName('VAT Return boxes')?->getCell('D2')->getValue());
                    $workbook->disconnectWorksheets();
                } finally {
                    File::delete($xlsxPath);
                }
            } finally {
                $zip->close();
            }
        } finally {
            File::delete($result['path']);
        }
    }

    public function test_pack_requires_a_locked_return_active_membership_and_permission(): void
    {
        $draft = $this->createReturn();
        $this->assertDomainFailure(
            fn () => app(GenerateAccountantPackAction::class)->execute($draft, $this->user),
            'locked',
        );

        $locked = $this->lock($draft);
        $this->user->revokePermissionTo('view_finance');
        $this->assertDomainFailure(
            fn () => app(GenerateAccountantPackAction::class)->execute($locked, $this->user),
            'cannot export',
        );

        $outsider = User::factory()->create(['is_active' => true]);
        $outsider->givePermissionTo('view_finance');
        $this->assertDomainFailure(
            fn () => app(GenerateAccountantPackAction::class)->execute($locked, $outsider),
            'does not belong',
        );
    }

    public function test_controller_downloads_the_pack_and_clean_attachment_tampering_is_rejected(): void
    {
        [$return, $attachments] = $this->lockedReturnWithAttachments();

        $response = $this->actingAs($this->user)
            ->get(route('admin.finance.vat-returns.accountant-pack', $return))
            ->assertOk()
            ->assertDownload()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Accountant-Pack-SHA256');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        File::delete($response->baseResponse->getFile()->getPathname());

        Storage::disk('financial')->put($attachments['clean']->path, 'tampered evidence');
        $this->assertDomainFailure(
            fn () => app(GenerateAccountantPackAction::class)->execute($return, $this->user),
            'SHA-256 integrity',
        );

        $staging = glob(storage_path('app/private/finance-packs/staging-*')) ?: [];
        $this->assertSame([], $staging);
    }

    /** @return array{VatReturn, array{clean: FinancialAttachment, pending: FinancialAttachment, rejected: FinancialAttachment}} */
    private function lockedReturnWithAttachments(): array
    {
        $adjustment = VatAdjustment::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => 'VADJ-2026-001',
            'status' => VatAdjustment::STATUS_DRAFT,
            'adjustment_type' => VatAdjustment::TYPE_ACCOUNTING_ADJUSTMENT,
            'vat_side' => VatAdjustment::SIDE_OUTPUT,
            'direction' => VatAdjustment::DIRECTION_INCREASE,
            'correction_method' => VatAdjustment::METHOD_NOT_ERROR,
            'tax_code_id' => $this->taxCode('S20')->id,
            'control_ledger_account_id' => $this->account('2200')->id,
            'contra_ledger_account_id' => $this->account('7900')->id,
            'effective_date' => '2026-03-15',
            'amount_gbp' => '20.00',
            'is_deliberate' => false,
            'reason' => 'Accountant pack evidence linkage fixture for quarterly VAT review.',
            'created_by' => $this->user->id,
        ]);

        $attachmentService = app(FinancialAttachmentService::class);
        $clean = $attachmentService->store(
            $adjustment,
            UploadedFile::fake()->createWithContent('=clean-evidence.pdf', "%PDF-1.4\nclean evidence\n"),
            $this->user,
            'vat_adjustment_evidence',
        );
        $clean->update(['scan_status' => FinancialAttachment::SCAN_CLEAN]);
        $pending = $attachmentService->store(
            $adjustment,
            UploadedFile::fake()->createWithContent('pending-evidence.pdf', "%PDF-1.4\npending evidence\n"),
            $this->user,
            'vat_adjustment_evidence',
        );
        $rejected = $attachmentService->store(
            $adjustment,
            UploadedFile::fake()->createWithContent('rejected-evidence.pdf', "%PDF-1.4\nrejected evidence\n"),
            $this->user,
            'vat_adjustment_evidence',
        );
        $rejected->update(['scan_status' => FinancialAttachment::SCAN_REJECTED]);

        app(PostJournalEntryAction::class)->execute($this->business, $this->user, [
            'entry_date' => '2026-03-15',
            'description' => 'Standard-rated VAT adjustment for accountant pack',
            'idempotency_key' => 'accountant-pack:vat-adjustment:2026-q1',
            'source' => $adjustment,
            'source_snapshot' => ['test' => true],
            'lines' => [
                ['ledger_account_id' => $this->account('1200')->id, 'debit_gbp' => '120.00', 'credit_gbp' => '0.00'],
                ['ledger_account_id' => $this->account('4100')->id, 'tax_code_id' => $this->taxCode('S20')->id, 'debit_gbp' => '0.00', 'credit_gbp' => '100.00'],
                ['ledger_account_id' => $this->account('2200')->id, 'tax_code_id' => $this->taxCode('S20')->id, 'debit_gbp' => '0.00', 'credit_gbp' => '20.00'],
            ],
        ]);

        return [$this->lock($this->createReturn()), compact('clean', 'pending', 'rejected')];
    }

    private function createReturn(): VatReturn
    {
        return app(CreateVatReturnReviewAction::class)->execute(
            $this->business,
            $this->user,
            '2026-01-01',
            '2026-03-31',
        );
    }

    private function lock(VatReturn $return): VatReturn
    {
        $reviewed = app(ReviewVatReturnAction::class)->execute($return, $this->user, true);

        return app(LockVatReturnAction::class)->execute(
            $reviewed,
            $this->user,
            'Quarter reviewed and locked for the accountant pack test.',
        );
    }

    private function account(string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function taxCode(string $code): TaxCode
    {
        return TaxCode::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function assertDomainFailure(callable $callback, string $message): void
    {
        try {
            $callback();
            $this->fail('Expected a DomainException to be thrown.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }
}
