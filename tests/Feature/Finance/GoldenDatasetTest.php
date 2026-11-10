<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateVatReturnReviewAction;
use App\Actions\Finance\GenerateAccountantPackAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\LockVatReturnAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\ReverseJournalEntryAction;
use App\Actions\Finance\ReviewVatReturnAction;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\TaxCode;
use App\Models\User;
use App\Models\VatAdjustment;
use App\Services\Finance\AgedReceivablesPayablesService;
use App\Services\Finance\BalanceSheetService;
use App\Services\Finance\ManagementCashFlowService;
use App\Services\Finance\ProfitAndLossService;
use App\Services\Finance\TrialBalanceService;
use App\Services\Finance\VatReturnSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use ZipArchive;

class GoldenDatasetTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE_SHA256 = '6271a6a9e179c1a8769e7fd957f401fde892b0421ea2a73bdc7a0918fd2672a1';

    /** @var array<string, mixed> */
    private array $fixture;

    private Business $business;

    private User $user;

    private AccountingPeriod $period;

    /** @var array<string, JournalEntry> */
    private array $journals = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = json_decode(
            File::get(resource_path('demo/uk-accountant-golden-dataset/v1.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->business = Business::create([
            'name' => $this->fixture['business_name'],
            'slug' => 'website-expert-ni-golden-v1',
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

        $permissions = [
            'access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance',
            'close_finance_periods', 'post_finance',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo($permissions);

        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $this->business->accountingProfile()->update([
            'vat_registered' => true,
            'vat_number' => $this->fixture['profile']['vat_number'],
            'vat_scheme' => $this->fixture['profile']['vat_scheme'],
            'vat_effective_from' => $this->fixture['profile']['vat_effective_from'],
        ]);
        $this->period = AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => $this->fixture['period']['name'],
            'financial_year' => $this->fixture['period']['financial_year'],
            'start_date' => $this->fixture['period']['from'],
            'end_date' => $this->fixture['period']['to'],
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->user->id,
        ]);

        $this->postFixture();
    }

    public function test_versioned_fixture_matches_all_finance_and_vat_control_totals(): void
    {
        $expected = $this->fixture['expected'];
        $trialBalance = app(TrialBalanceService::class)->report($this->business->id, $this->period->id);
        $profitAndLoss = app(ProfitAndLossService::class)->report($this->business->id, $this->period->id);
        $balanceSheet = app(BalanceSheetService::class)->report($this->business->id, $this->period->id);
        $cashFlow = app(ManagementCashFlowService::class)->report($this->business->id, $this->period->id);
        $ageing = app(AgedReceivablesPayablesService::class)->report($this->business->id, $this->fixture['period']['to']);

        $this->assertSame(
            self::FIXTURE_SHA256,
            hash_file('sha256', resource_path('demo/uk-accountant-golden-dataset/v1.json')),
            'Golden dataset v1 changed in place. Create a new version and obtain a new accountant sign-off.',
        );
        $this->assertSame(1, $this->fixture['schema_version']);
        $this->assertSame('pending', $this->fixture['review_status']);
        $this->assertSame($expected['trial_balance']['closing_debits'], $trialBalance['totals']->closing_debit_gbp);
        $this->assertSame($expected['trial_balance']['closing_credits'], $trialBalance['totals']->closing_credit_gbp);
        $this->assertSame($expected['trial_balance']['difference'], $trialBalance['totals']->closing_difference_gbp);
        $this->assertTrue($trialBalance['totals']->is_balanced);

        $this->assertSame($expected['profit_and_loss']['sales'], $profitAndLoss['totals']->sales_gbp);
        $this->assertSame($expected['profit_and_loss']['operating_expenses'], $profitAndLoss['totals']->operating_expenses_gbp);
        $this->assertSame($expected['profit_and_loss']['other_income'], $profitAndLoss['totals']->other_income_gbp);
        $this->assertSame($expected['profit_and_loss']['other_expenses'], $profitAndLoss['totals']->other_expenses_gbp);
        $this->assertSame($expected['profit_and_loss']['net_profit'], $profitAndLoss['totals']->net_profit_gbp);

        $this->assertSame($expected['balance_sheet']['assets'], $balanceSheet['totals']->total_assets_gbp);
        $this->assertSame($expected['balance_sheet']['liabilities'], $balanceSheet['totals']->total_liabilities_gbp);
        $this->assertSame($expected['balance_sheet']['ledger_equity'], $balanceSheet['totals']->ledger_equity_gbp);
        $this->assertSame($expected['balance_sheet']['period_earnings'], $balanceSheet['totals']->period_earnings_gbp);
        $this->assertSame($expected['balance_sheet']['equity'], $balanceSheet['totals']->total_equity_gbp);
        $this->assertSame($expected['balance_sheet']['liabilities_and_equity'], $balanceSheet['totals']->liabilities_and_equity_gbp);
        $this->assertSame($expected['balance_sheet']['difference'], $balanceSheet['totals']->difference_gbp);
        $this->assertTrue($balanceSheet['totals']->is_balanced);

        $this->assertSame($expected['cash_flow']['inflows'], $cashFlow['totals']->cash_inflows_gbp);
        $this->assertSame($expected['cash_flow']['outflows'], $cashFlow['totals']->cash_outflows_gbp);
        $this->assertSame($expected['cash_flow']['operating'], $cashFlow['totals']->operating_cash_flow_gbp);
        $this->assertSame($expected['cash_flow']['investing'], $cashFlow['totals']->investing_cash_flow_gbp);
        $this->assertSame($expected['cash_flow']['financing'], $cashFlow['totals']->financing_cash_flow_gbp);
        $this->assertSame($expected['cash_flow']['net'], $cashFlow['totals']->net_cash_flow_gbp);
        $this->assertSame($expected['cash_flow']['closing'], $cashFlow['totals']->closing_cash_gbp);
        $this->assertSame($expected['cash_flow']['difference'], $cashFlow['totals']->difference_gbp);
        $this->assertTrue($cashFlow['totals']->is_reconciled);

        $ageingSections = $ageing['sections']->keyBy('key');
        $this->assertSame($expected['ageing']['receivables'], $ageingSections['receivables']->totals->total_gbp);
        $this->assertSame($expected['ageing']['payables'], $ageingSections['payables']->totals->total_gbp);
        $this->assertTrue($ageingSections['receivables']->totals->is_reconciled);
        $this->assertTrue($ageingSections['payables']->totals->is_reconciled);

        $firstSnapshot = app(VatReturnSnapshotService::class)->build(
            $this->business->id,
            $this->fixture['period']['from'],
            $this->fixture['period']['to'],
        );
        $secondSnapshot = app(VatReturnSnapshotService::class)->build(
            $this->business->id,
            $this->fixture['period']['from'],
            $this->fixture['period']['to'],
        );
        $this->assertSame($firstSnapshot['hash'], $secondSnapshot['hash']);
        $this->assertSame($firstSnapshot['snapshot'], $secondSnapshot['snapshot']);

        $boxes = collect(data_get($firstSnapshot, 'snapshot.calculation.boxes'))->keyBy('number');
        foreach (range(1, 9) as $box) {
            $this->assertSame(
                $expected['vat_return']['box_'.$box],
                $boxes[$box]['submission_value'],
                "VAT Return box {$box} differs from golden dataset v1.",
            );
        }
        $this->assertSame($expected['vat_return']['net_direction'], data_get($firstSnapshot, 'snapshot.calculation.net_direction'));
        $this->assertTrue(data_get($firstSnapshot, 'snapshot.calculation.checks.vat_detail_reconciled'));
        $this->assertTrue(data_get($firstSnapshot, 'snapshot.calculation.checks.output_vat_mapped'));
        $this->assertTrue(data_get($firstSnapshot, 'snapshot.calculation.checks.input_vat_mapped'));
    }

    public function test_locked_golden_snapshot_generates_the_real_accountant_review_pack(): void
    {
        $return = app(CreateVatReturnReviewAction::class)->execute(
            $this->business,
            $this->user,
            $this->fixture['period']['from'],
            $this->fixture['period']['to'],
        );
        $reviewed = app(ReviewVatReturnAction::class)->execute(
            $return,
            $this->user,
            true,
            'Golden dataset v1 technical reconciliation completed; accountant sign-off remains pending.',
        );
        $locked = app(LockVatReturnAction::class)->execute(
            $reviewed,
            $this->user,
            'Golden dataset v1 locked for external accountant review.',
        );
        $pack = app(GenerateAccountantPackAction::class)->execute($locked, $this->user);

        try {
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($pack['path']));

            try {
                $manifest = json_decode((string) $zip->getFromName('manifest.sha256.json'), true, 512, JSON_THROW_ON_ERROR);
                $this->assertSame('Website Expert NI', $manifest['business']['name']);
                $this->assertSame($locked->snapshot_hash, $manifest['vat_return']['snapshot_hash']);
                $this->assertStringNotContainsString($this->fixture['profile']['vat_number'], (string) $zip->getFromName('README.txt'));
                $this->assertStringContainsString('NOT SUBMITTED TO HMRC', (string) $zip->getFromName('README.txt'));
                $this->assertNotFalse($zip->getFromName('accountant-pack.xlsx'));
                $this->assertNotFalse($zip->getFromName('vat-return-summary.pdf'));
            } finally {
                $zip->close();
            }
        } finally {
            File::delete($pack['path']);
        }
    }

    private function postFixture(): void
    {
        foreach ($this->fixture['journals'] as $journal) {
            if (isset($journal['reverse_of'])) {
                $this->journals[$journal['key']] = app(ReverseJournalEntryAction::class)->execute(
                    $this->journals[$journal['reverse_of']],
                    $this->user,
                    $journal['description'],
                    $journal['date'],
                );

                continue;
            }

            $source = ($journal['source'] ?? null) === 'vat_adjustment'
                ? $this->vatAdjustment($journal)
                : null;
            $this->journals[$journal['key']] = app(PostJournalEntryAction::class)->execute(
                $this->business,
                $this->user,
                [
                    'entry_date' => $journal['date'],
                    'description' => $journal['description'],
                    'idempotency_key' => 'golden-v1:'.$journal['key'],
                    'source' => $source,
                    'source_snapshot' => [
                        'dataset_id' => $this->fixture['dataset_id'],
                        'journal_key' => $journal['key'],
                    ],
                    'lines' => array_map(fn (array $line): array => $this->line($line), $journal['lines']),
                ],
            );
        }
    }

    /** @param array<string, mixed> $line @return array<string, mixed> */
    private function line(array $line): array
    {
        return array_filter([
            'ledger_account_id' => $this->account($line['account'])->id,
            'tax_code_id' => isset($line['tax']) ? $this->taxCode($line['tax'])->id : null,
            'debit_gbp' => $line['debit'],
            'credit_gbp' => $line['credit'],
            'original_currency' => $line['currency'] ?? 'GBP',
            'original_amount' => $line['original_amount'] ?? null,
            'exchange_rate' => $line['exchange_rate'] ?? null,
        ], fn (mixed $value): bool => $value !== null);
    }

    /** @param array<string, mixed> $journal */
    private function vatAdjustment(array $journal): VatAdjustment
    {
        return VatAdjustment::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => 'VADJ-GOLDEN-V1',
            'status' => VatAdjustment::STATUS_DRAFT,
            'adjustment_type' => VatAdjustment::TYPE_ACCOUNTING_ADJUSTMENT,
            'vat_side' => VatAdjustment::SIDE_OUTPUT,
            'direction' => VatAdjustment::DIRECTION_INCREASE,
            'correction_method' => VatAdjustment::METHOD_NOT_ERROR,
            'tax_code_id' => $this->taxCode('S20')->id,
            'control_ledger_account_id' => $this->account('2200')->id,
            'contra_ledger_account_id' => $this->account('7900')->id,
            'effective_date' => $journal['date'],
            'amount_gbp' => '5.00',
            'is_deliberate' => false,
            'reason' => 'Fictional documented adjustment included in golden dataset v1.',
            'created_by' => $this->user->id,
        ]);
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
}
