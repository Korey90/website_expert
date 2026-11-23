<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CalculateUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\ClassifyUniversalCreditReportItemAction;
use App\Actions\Finance\CreateUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\CreateUniversalCreditManualEntryAction;
use App\Actions\Finance\CreateUniversalCreditSimplifiedExpenseAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\LockUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\MarkUniversalCreditAssessmentPeriodReportedAction;
use App\Actions\Finance\RequestUniversalCreditEvidencePackAction;
use App\Actions\Finance\ReviewUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\UpdateUniversalCreditProfileAction;
use App\Jobs\GenerateUniversalCreditEvidencePackJob;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditEvidencePack;
use App\Models\User;
use App\Services\Finance\UniversalCreditEvidencePackGenerator;
use App\Services\Finance\UniversalCreditPreflightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use ZipArchive;

class UniversalCreditGoldenDatasetTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE_SHA256 = 'ef92a67e1c458f001334f4659f9dd87bc54986cd2660c3fd757dd3caa6f0436a';

    /** @var array<string, mixed> */
    private array $fixture;

    private Business $business;

    private User $owner;

    private UniversalCreditAssessmentPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('financial');
        Queue::fake();
        $this->fixture = json_decode(
            File::get(resource_path('demo/uk-universal-credit-golden-dataset/v1.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->business = Business::create([
            'name' => $this->fixture['business_name'],
            'slug' => 'website-expert-ni-uc-golden-v1',
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
        foreach ([
            'view_universal_credit_reporting',
            'manage_universal_credit_reporting',
            'export_universal_credit_pack',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->owner->givePermissionTo([
            'view_universal_credit_reporting',
            'manage_universal_credit_reporting',
            'export_universal_credit_pack',
        ]);
        $this->actingAs($this->owner);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->owner)['profile']->update([
            'entity_type' => 'sole_trader',
            'legal_name' => $this->fixture['business_name'],
            'vat_registered' => false,
        ]);
        app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, ['is_enabled' => true]);
        $this->period = app(CreateUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->business,
            $this->owner,
            $this->fixture['assessment_period']['from'],
            $this->fixture['assessment_period']['to'],
        );
    }

    public function test_versioned_candidate_completes_the_isolated_end_to_end_dry_run(): void
    {
        $this->assertSame(
            self::FIXTURE_SHA256,
            hash_file('sha256', resource_path('demo/uk-universal-credit-golden-dataset/v1.json')),
            'Universal Credit golden dataset v1 changed in place. Create v2 and obtain a new specialist review.',
        );
        $this->assertSame(1, $this->fixture['schema_version']);
        $this->assertSame('pending_specialist_review', $this->fixture['review_status']);

        foreach ($this->fixture['manual_entries'] as $row) {
            $entry = app(CreateUniversalCreditManualEntryAction::class)->execute(
                $this->period,
                $this->owner,
                [
                    'direction' => $row['direction'],
                    'event_type' => $row['event_type'],
                    'category' => $row['category'],
                    'cash_date' => $row['cash_date'],
                    'original_amount' => $row['amount_gbp'],
                    'original_currency' => 'GBP',
                    'description' => $row['description'],
                    'evidence_missing_reason' => 'Golden dataset uses documented synthetic evidence.',
                ],
            );
            app(ClassifyUniversalCreditReportItemAction::class)->execute(
                $entry->reportItems->firstOrFail(),
                $this->owner,
                $row['classification'],
                $row['category'],
                $row['business_use_percentage'] ?? 100,
                $row['business_use_reason'] ?? null,
                $row['classification_reason'] ?? null,
                $row['calculation_rule'] ?? null,
            );
        }

        foreach ($this->fixture['simplified_expenses'] as $row) {
            app(CreateUniversalCreditSimplifiedExpenseAction::class)->execute(
                $this->period,
                $this->owner,
                $row['type'],
                $row['inputs'],
                $row['description'],
                'Golden dataset uses documented synthetic evidence.',
            );
        }

        $this->period = app(CalculateUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->period->fresh(),
            $this->owner,
        );
        $expected = $this->fixture['expected'];
        $this->assertSame($expected['income_total_pence'], $this->period->income_total_pence);
        $this->assertSame($expected['expense_total_pence'], $this->period->expense_total_pence);
        $this->assertSame($expected['net_total_pence'], $this->period->net_total_pence);
        $this->assertSame($expected['included_report_items'], $this->period->reportItems()->where('classification_status', 'included')->count());
        $this->assertSame($expected['excluded_report_items'], $this->period->reportItems()->where('classification_status', 'excluded')->count());
        $this->assertSame($expected['active_simplified_expenses'], $this->period->simplifiedExpenses()->where('status', 'active')->count());

        foreach ($expected['category_reportable_pence'] as $category => $amount) {
            $this->assertSame($amount, (int) $this->period->reportItems()->where('category', $category)->sum('reportable_amount_pence'));
        }
        foreach ($expected['simplified_reportable_pence'] as $type => $amount) {
            $this->assertSame($amount, (int) $this->period->simplifiedExpenses()->where('type', $type)->sum('calculated_amount_pence'));
        }

        $preflight = app(UniversalCreditPreflightService::class)->inspect($this->period);
        $this->assertSame($expected['preflight_status_before_review'], $preflight->status());
        $this->period = app(ReviewUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->period,
            $this->owner,
            true,
            true,
            'Golden dataset candidate reviewed against its deterministic expected totals.',
        );
        $this->period = app(LockUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->period,
            $this->owner,
            'Golden dataset candidate locked for an isolated end-to-end dry run.',
        );

        $pack = app(RequestUniversalCreditEvidencePackAction::class)->execute($this->period, $this->owner, 'en');
        (new GenerateUniversalCreditEvidencePackJob($pack->id))->handle(app(UniversalCreditEvidencePackGenerator::class));
        $pack = $pack->fresh();
        $this->assertSame(UniversalCreditEvidencePack::STATUS_COMPLETED, $pack->status);
        $this->assertSame($this->period->review_snapshot_hash, $pack->snapshot_hash);
        $this->assertSame(64, strlen((string) $pack->zip_sha256));
        Storage::disk('financial')->assertExists($pack->path);
        $this->assertPackContainsRequiredReports($pack);
        $this->exportSpecialistReviewArtifactWhenRequested($pack);

        $this->period = app(MarkUniversalCreditAssessmentPeriodReportedAction::class)->execute(
            $this->period,
            $this->owner,
            'GOLDEN-V1-DRY-RUN',
        );
        $this->assertSame($expected['final_status'], $this->period->status);
        $this->assertSame('GOLDEN-V1-DRY-RUN', $this->period->report_confirmation_reference);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit passed.')
            ->assertSuccessful();
    }

    private function assertPackContainsRequiredReports(UniversalCreditEvidencePack $pack): void
    {
        $path = tempnam(sys_get_temp_dir(), 'uc-golden-');
        File::put($path, Storage::disk($pack->disk)->get($pack->path));
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($path) === true);

        try {
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
            ] as $required) {
                $this->assertNotFalse($archive->locateName($required), $required);
            }
        } finally {
            $archive->close();
            File::delete($path);
        }
    }

    private function exportSpecialistReviewArtifactWhenRequested(UniversalCreditEvidencePack $pack): void
    {
        if (getenv('GENERATE_UC_GOLDEN_PACK') !== '1') {
            return;
        }

        $directory = base_path('docs/demo/uk-universal-credit-golden-v1');
        $zipPath = $directory.DIRECTORY_SEPARATOR.'universal-credit-golden-v1.zip';
        $metadataPath = $directory.DIRECTORY_SEPARATOR.'artifact.sha256.json';
        File::ensureDirectoryExists($directory);
        File::put($zipPath, Storage::disk($pack->disk)->get($pack->path));

        $zipHash = hash_file('sha256', $zipPath);
        $this->assertSame($pack->zip_sha256, $zipHash);
        $this->assertSame($pack->bytes, File::size($zipPath));

        File::put($metadataPath, json_encode([
            'classification' => 'DEMO / NOT FOR SUBMISSION',
            'dataset_id' => $this->fixture['dataset_id'],
            'dataset_sha256' => self::FIXTURE_SHA256,
            'review_status' => $this->fixture['review_status'],
            'review_snapshot_sha256' => $this->period->review_snapshot_hash,
            'artifact' => basename($zipPath),
            'artifact_sha256' => $zipHash,
            'artifact_bytes' => File::size($zipPath),
            'generated_at' => now()->utc()->toIso8601String(),
            'generator' => self::class,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
    }
}
