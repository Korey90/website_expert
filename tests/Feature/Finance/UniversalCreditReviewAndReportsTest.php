<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CalculateUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\ClassifyUniversalCreditReportItemAction;
use App\Actions\Finance\CreateUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\CreateUniversalCreditManualEntryAction;
use App\Actions\Finance\ExportUniversalCreditAssessmentPeriodReportAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\LockUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\MarkUniversalCreditAssessmentPeriodReportedAction;
use App\Actions\Finance\ReviewUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\SupersedeUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\UpdateUniversalCreditProfileAction;
use App\Filament\Resources\UniversalCreditAssessmentPeriodResource;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\FinancialAttachment;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditReportItem;
use App\Models\User;
use App\Services\Finance\UniversalCreditPreflightService;
use App\Services\Finance\UniversalCreditReportDocumentFactory;
use App\Services\Finance\UniversalCreditReportModelService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class UniversalCreditReviewAndReportsTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    private UniversalCreditAssessmentPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('access_admin_panel', 'web');

        $this->business = Business::create([
            'name' => 'Website Expert NI',
            'slug' => 'website-expert-ni-uc-review',
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
            'access_admin_panel',
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

    public function test_empty_period_has_green_preflight_and_can_be_reviewed_and_locked(): void
    {
        $this->calculate();
        $preflight = app(UniversalCreditPreflightService::class)->inspect($this->period->fresh());

        $this->assertSame('green', $preflight->status());
        $reviewed = app(ReviewUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->period->fresh(), $this->owner, true,
        );
        $this->assertSame(UniversalCreditAssessmentPeriod::STATUS_IN_REVIEW, $reviewed->status);
        $this->assertSame(64, strlen((string) $reviewed->review_snapshot_hash));
        $this->assertSame(1, $reviewed->review_snapshot_version);

        $locked = app(LockUniversalCreditAssessmentPeriodAction::class)->execute(
            $reviewed, $this->owner, 'Reviewed against the complete source snapshot.',
        );
        $this->assertSame(UniversalCreditAssessmentPeriod::STATUS_LOCKED, $locked->status);
        $this->assertSame($reviewed->review_snapshot_hash, $locked->review_snapshot_hash);

        $this->expectException(DomainException::class);
        $locked->update(['income_total_pence' => 1]);
    }

    public function test_preflight_blocks_incomplete_calculation_and_requires_warning_confirmation(): void
    {
        $item = $this->manualIncome('50.00');
        $result = app(UniversalCreditPreflightService::class)->inspect($this->period->fresh());
        $this->assertSame('red', $result->status());
        $this->assertContains('classification_required', array_column($result->errors, 'code'));

        app(ClassifyUniversalCreditReportItemAction::class)->execute(
            $item, $this->owner, UniversalCreditReportItem::STATUS_INCLUDED, 'goods_and_services',
        );
        $this->calculate();
        $result = app(UniversalCreditPreflightService::class)->inspect($this->period->fresh());
        $this->assertSame('amber', $result->status());
        $this->assertContains('evidence_missing', array_column($result->warnings, 'code'));

        try {
            app(ReviewUniversalCreditAssessmentPeriodAction::class)->execute(
                $this->period->fresh(), $this->owner, true, false,
            );
            $this->fail('Warnings must require explicit confirmation.');
        } catch (DomainException $exception) {
            $this->assertSame(
                __('finance.universal_credit.errors.preflight_warnings_confirmation_required'),
                $exception->getMessage(),
            );
        }

        $reviewed = app(ReviewUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->period->fresh(), $this->owner, true, true,
        );
        $this->assertSame(1, $reviewed->preflight_warning_count);
    }

    public function test_evidence_change_after_review_invalidates_the_lock_snapshot(): void
    {
        $item = $this->manualIncome('50.00');
        app(ClassifyUniversalCreditReportItemAction::class)->execute(
            $item, $this->owner, UniversalCreditReportItem::STATUS_INCLUDED, 'goods_and_services',
        );
        $this->calculate();
        $reviewed = app(ReviewUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->period->fresh(), $this->owner, true, true,
        );
        $entry = UniversalCreditManualEntry::withoutGlobalScopes()->findOrFail($item->source_id);
        FinancialAttachment::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'attachable_type' => $entry->getMorphClass(),
            'attachable_id' => $entry->id,
            'uploaded_by' => $this->owner->id,
            'document_type' => 'receipt',
            'disk' => 'financial',
            'path' => 'test/clean.pdf',
            'original_name' => 'clean.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'sha256' => hash('sha256', 'clean-evidence'),
            'scan_status' => FinancialAttachment::SCAN_CLEAN,
            'retention_until' => '2033-11-23',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('finance.universal_credit.errors.review_snapshot_changed'));
        app(LockUniversalCreditAssessmentPeriodAction::class)->execute(
            $reviewed, $this->owner, 'Lock after evidence was added to the reviewed period.',
        );
    }

    public function test_report_model_and_all_formats_use_identical_totals(): void
    {
        $item = $this->manualIncome('50.00');
        app(ClassifyUniversalCreditReportItemAction::class)->execute(
            $item, $this->owner, UniversalCreditReportItem::STATUS_INCLUDED, 'goods_and_services',
        );
        $this->calculate();
        $report = app(UniversalCreditReportModelService::class)->build($this->period->fresh());
        $document = app(UniversalCreditReportDocumentFactory::class)->make($this->business, $report);

        $this->assertSame(5000, $report['summary']['income_total_pence']);
        $this->assertSame(5000, $report['details'][0]['reportable_amount_pence']);
        $this->assertSame('50.00', $document['tables'][0]['rows'][0]['values'][1]);

        foreach (['csv', 'xlsx', 'pdf'] as $format) {
            $response = app(ExportUniversalCreditAssessmentPeriodReportAction::class)->execute(
                $this->period->fresh(), $this->owner, $format,
            );
            $content = $this->responseContent($response);
            $this->assertStringContainsString('private', (string) $response->headers->get('cache-control'));

            if ($format === 'csv') {
                $this->assertStringContainsString('50.00', $content);
            } elseif ($format === 'xlsx') {
                $this->assertStringStartsWith("PK\x03\x04", $content);
                $path = tempnam(sys_get_temp_dir(), 'uc-xlsx-');
                file_put_contents($path, $content);
                $spreadsheet = IOFactory::load($path);
                $this->assertSame(50.0, (float) $spreadsheet->getActiveSheet()->getCell('B11')->getValue());
                $spreadsheet->disconnectWorksheets();
                @unlink($path);
            } else {
                $this->assertStringStartsWith('%PDF-', $content);
            }
        }
    }

    public function test_supersede_is_controlled_and_export_is_tenant_and_permission_protected(): void
    {
        $this->calculate();
        $superseded = app(SupersedeUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->period->fresh(), $this->owner, 'This period will be recreated from corrected source data.',
        );
        $this->assertSame(UniversalCreditAssessmentPeriod::STATUS_SUPERSEDED, $superseded->status);
        $this->assertSame($this->owner->id, $superseded->superseded_by_user_id);

        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id, 'user_id' => $member->id, 'role' => 'member',
            'is_active' => true, 'joined_at' => now(),
        ]);

        $this->expectException(DomainException::class);
        app(ExportUniversalCreditAssessmentPeriodReportAction::class)->execute($superseded, $member, 'csv');
    }

    public function test_locked_period_can_be_marked_as_reported_with_immutable_confirmation(): void
    {
        Storage::fake('financial');
        Queue::fake();
        $this->calculate();
        $reviewed = app(ReviewUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->period->fresh(), $this->owner, true,
        );
        $locked = app(LockUniversalCreditAssessmentPeriodAction::class)->execute(
            $reviewed, $this->owner, 'Reviewed and ready for manual Universal Credit submission.',
        );
        $reported = app(MarkUniversalCreditAssessmentPeriodReportedAction::class)->execute(
            $locked,
            $this->owner,
            'UC-CONFIRM-2026-001',
            UploadedFile::fake()->createWithContent('uc-confirmation.pdf', "%PDF-1.4\nUC confirmation\n%%EOF"),
        );

        $this->assertSame(UniversalCreditAssessmentPeriod::STATUS_REPORTED, $reported->status);
        $this->assertSame($this->owner->id, $reported->reported_by);
        $this->assertNotNull($reported->reported_at);
        $this->assertSame('UC-CONFIRM-2026-001', $reported->report_confirmation_reference);
        $this->assertCount(1, $reported->attachments);
        Storage::disk('financial')->assertExists($reported->attachments->first()->path);

        $this->expectException(DomainException::class);
        $reported->update(['report_confirmation_reference' => 'CHANGED']);
    }

    public function test_authorised_owner_can_open_the_panel_and_export_through_the_protected_route(): void
    {
        $this->assertTrue(UniversalCreditAssessmentPeriodResource::canAccess());
        $this->get(UniversalCreditAssessmentPeriodResource::getUrl())->assertOk()
            ->assertSee(__('finance.universal_credit.ui.navigation'));
        $this->get(UniversalCreditAssessmentPeriodResource::getUrl('view', ['record' => $this->period]))
            ->assertOk()
            ->assertSee($this->period->number);

        $this->get(route('admin.finance.universal-credit.assessment-periods.report', [
            'assessmentPeriod' => $this->period,
            'format' => 'csv',
        ]))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $viewer = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $viewer->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $viewer->givePermissionTo(['access_admin_panel', 'view_universal_credit_reporting']);
        $this->actingAs($viewer)
            ->get(route('admin.finance.universal-credit.assessment-periods.report', [
                'assessmentPeriod' => $this->period,
                'format' => 'csv',
            ]))
            ->assertForbidden();
    }

    public function test_period_page_renders_preflight_issue_context_without_a_type_error(): void
    {
        $item = $this->manualIncome('50.00');

        $this->get(UniversalCreditAssessmentPeriodResource::getUrl('view', ['record' => $this->period]))
            ->assertOk()
            ->assertSee(__('finance.universal_credit.preflight.classification_required'))
            ->assertSee('"item_id":'.$item->id);
    }

    public function test_universal_credit_ui_translations_have_en_pl_pt_key_parity(): void
    {
        $keys = [];

        foreach (['en', 'pl', 'pt'] as $locale) {
            $translations = require lang_path("{$locale}/finance.php");
            $keys[$locale] = array_keys(Arr::dot($translations['universal_credit']['ui']));
            sort($keys[$locale]);
        }

        $this->assertSame($keys['en'], $keys['pl']);
        $this->assertSame($keys['en'], $keys['pt']);
    }

    public function test_tenant_audit_detects_a_tampered_review_snapshot_hash(): void
    {
        $this->calculate();
        $reviewed = app(ReviewUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->period->fresh(), $this->owner, true,
        );
        DB::table('universal_credit_assessment_periods')->where('id', $reviewed->id)->update([
            'review_snapshot_hash' => str_repeat('0', 64),
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('universal_credit_assessment_periods.review_snapshot')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }

    private function manualIncome(string $amount): UniversalCreditReportItem
    {
        $entry = app(CreateUniversalCreditManualEntryAction::class)->execute($this->period, $this->owner, [
            'direction' => UniversalCreditReportItem::DIRECTION_INCOME,
            'event_type' => UniversalCreditManualEntry::EVENT_CASH_RECEIPT,
            'category' => 'goods_and_services',
            'cash_date' => '2026-12-01',
            'original_amount' => $amount,
            'original_currency' => 'GBP',
            'description' => 'Cash receipt for a completed website service.',
            'evidence_missing_reason' => 'Customer receipt is not available for this test.',
        ]);

        return $entry->reportItems->firstOrFail();
    }

    private function calculate(): void
    {
        $this->period = app(CalculateUniversalCreditAssessmentPeriodAction::class)
            ->execute($this->period->fresh(), $this->owner);
    }

    private function responseContent(Response $response): string
    {
        if ($response instanceof StreamedResponse) {
            ob_start();
            $response->sendContent();

            return (string) ob_get_clean();
        }

        return (string) $response->getContent();
    }
}
