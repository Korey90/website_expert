<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\ReverseJournalEntryAction;
use App\Filament\Pages\BalanceSheetPage;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Services\Finance\BalanceSheetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BalanceSheetReportTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private AccountingPeriod $comparisonPeriod;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Balance Sheet Test Business',
            'slug' => 'balance-sheet-test',
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
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);

        foreach (['access_admin_panel', 'view_finance', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'post_finance']);

        $this->comparisonPeriod = $this->createPeriod('FY 2025', 2025, '2025-01-01', '2025-12-31');
        $this->period = $this->createPeriod('FY 2026', 2026, '2026-01-01', '2026-12-31');
    }

    public function test_balance_sheet_calculates_comparison_unclosed_earnings_and_control_difference(): void
    {
        $this->postJournal('2025-01-02', 'capital', [
            $this->line('1100', '1000.00', '0.00'),
            $this->line('3100', '0.00', '1000.00'),
        ]);
        $this->postJournal('2025-06-01', 'prior-sale', [
            $this->line('1100', '200.00', '0.00'),
            $this->line('4100', '0.00', '200.00'),
        ]);
        $this->postJournal('2025-07-01', 'prior-expense', [
            $this->line('6200', '50.00', '0.00'),
            $this->line('1100', '0.00', '50.00'),
        ]);
        $this->postJournal('2026-03-01', 'credit-sale', [
            $this->line('1200', '300.00', '0.00'),
            $this->line('4100', '0.00', '300.00'),
        ]);
        $this->postJournal('2026-04-01', 'supplier-expense', [
            $this->line('6200', '100.00', '0.00'),
            $this->line('2100', '0.00', '100.00'),
        ]);
        $this->postJournal('2026-05-01', 'equipment', [
            $this->line('1500', '400.00', '0.00'),
            $this->line('1100', '0.00', '400.00'),
        ]);
        $this->postJournal('2026-12-01', 'depreciation', [
            $this->line('7000', '40.00', '0.00'),
            $this->line('1600', '0.00', '40.00'),
        ]);
        $this->account('1500')->update(['is_active' => false]);

        $report = app(BalanceSheetService::class)->report(
            $this->business->id,
            $this->period->id,
            $this->comparisonPeriod->id,
        );
        $rows = $report['sections']->flatMap(fn (object $section) => $section->rows);
        $accountRows = $rows->reject(fn (object $row): bool => $row->is_synthetic)->keyBy('code');
        $syntheticRows = $rows->filter(fn (object $row): bool => $row->is_synthetic)->keyBy('synthetic_key');

        $this->assertSame('FY 2026', $report['period']->name);
        $this->assertSame('FY 2025', $report['comparison_period']->name);
        $this->assertSame(750.0, $accountRows['1100']->amount_gbp);
        $this->assertSame(1150.0, $accountRows['1100']->comparison_gbp);
        $this->assertSame(-400.0, $accountRows['1100']->variance_gbp);
        $this->assertSame(400.0, $accountRows['1500']->amount_gbp);
        $this->assertFalse($accountRows['1500']->is_active);
        $this->assertSame(-40.0, $accountRows['1600']->amount_gbp);
        $this->assertSame(150.0, $syntheticRows['earnings_before_period']->amount_gbp);
        $this->assertSame(160.0, $syntheticRows['period_earnings']->amount_gbp);
        $this->assertSame(1410.0, $report['totals']->total_assets_gbp);
        $this->assertSame(1150.0, $report['totals']->comparison_total_assets_gbp);
        $this->assertSame(260.0, $report['totals']->variance_total_assets_gbp);
        $this->assertSame(100.0, $report['totals']->total_liabilities_gbp);
        $this->assertSame(1000.0, $report['totals']->ledger_equity_gbp);
        $this->assertSame(310.0, $report['totals']->accumulated_earnings_gbp);
        $this->assertSame(1310.0, $report['totals']->total_equity_gbp);
        $this->assertSame(1410.0, $report['totals']->liabilities_and_equity_gbp);
        $this->assertSame(0.0, $report['totals']->difference_gbp);
        $this->assertTrue($report['totals']->is_balanced);
        $this->assertTrue($report['totals']->comparison_is_balanced);
        $this->assertSame(6, $report['totals']->account_count);
        $this->assertSame(7, $report['totals']->journal_count);
    }

    public function test_reversed_journal_remains_auditable_and_nets_to_zero(): void
    {
        $original = $this->postJournal('2026-05-01', 'mistaken-sale', [
            $this->line('1100', '12.34', '0.00'),
            $this->line('4100', '0.00', '12.34'),
        ]);
        app(ReverseJournalEntryAction::class)->execute($original, $this->user, 'Remove balance sheet test error', '2026-05-02');

        $report = app(BalanceSheetService::class)->report($this->business->id, $this->period->id);
        $assets = $report['sections']->firstWhere('key', 'assets');

        $this->assertCount(1, $assets->rows);
        $this->assertSame(0.0, $assets->rows->first()->amount_gbp);
        $this->assertSame(0.0, $report['totals']->period_earnings_gbp);
        $this->assertSame(0.0, $report['totals']->difference_gbp);
        $this->assertTrue($report['totals']->is_balanced);
        $this->assertSame(2, $report['totals']->journal_count);
    }

    public function test_empty_period_is_balanced_and_only_contains_earnings_rows(): void
    {
        $report = app(BalanceSheetService::class)->report($this->business->id, $this->period->id);
        $equity = $report['sections']->firstWhere('key', 'equity');

        $this->assertSame(0.0, $report['totals']->total_assets_gbp);
        $this->assertSame(0.0, $report['totals']->liabilities_and_equity_gbp);
        $this->assertSame(0.0, $report['totals']->difference_gbp);
        $this->assertNull($report['totals']->comparison_difference_gbp);
        $this->assertTrue($report['totals']->is_balanced);
        $this->assertNull($report['totals']->comparison_is_balanced);
        $this->assertSame(0, $report['totals']->account_count);
        $this->assertCount(2, $equity->rows);
        $this->assertTrue($equity->rows->every(fn (object $row): bool => $row->is_synthetic));
    }

    public function test_service_rejects_foreign_and_identical_comparison_periods(): void
    {
        try {
            app(BalanceSheetService::class)->report(
                $this->business->id,
                $this->period->id,
                $this->period->id,
            );
            $this->fail('An identical comparison period should fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('must differ', $exception->getMessage());
        }

        [$otherBusiness, $otherUser] = $this->otherTenant();
        $foreignPeriod = AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Private FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $otherUser->id,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not belong');
        app(BalanceSheetService::class)->report($this->business->id, $foreignPeriod->id);
    }

    public function test_authorized_user_can_open_page_and_export_tenant_csv(): void
    {
        $this->postJournal('2026-07-01', 'export-capital', [
            $this->line('1100', '75.00', '0.00'),
            $this->line('3100', '0.00', '75.00'),
        ]);
        [$otherBusiness, $otherUser] = $this->otherTenant();
        AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Private comparison period',
            'financial_year' => 2024,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $otherUser->id,
        ]);

        $this->actingAs($this->user)
            ->get(BalanceSheetPage::getUrl())
            ->assertOk()
            ->assertSee(__('finance.balance_sheet.title'))
            ->assertSee('FY 2026')
            ->assertDontSee('Private comparison period');

        $response = $this->get(route('admin.finance.balance-sheet.csv', [
            'period_id' => $this->period->id,
            'comparison_period_id' => $this->comparisonPeriod->id,
        ]));
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('cache-control', 'no-store, private');
        $content = $response->streamedContent();

        $this->assertStringContainsString('BALANCE SHEET', $content);
        $this->assertStringContainsString('FY 2026', $content);
        $this->assertStringContainsString('3100', $content);
        $this->assertStringContainsString('Total liabilities and equity', $content);
        $this->assertStringNotContainsString('Private comparison period', $content);
    }

    public function test_permission_and_tenant_validation_protect_page_and_export(): void
    {
        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $member->id,
            'role' => 'member',
            'is_active' => true,
        ]);
        $member->givePermissionTo('access_admin_panel');

        $this->actingAs($member)->get(BalanceSheetPage::getUrl())->assertForbidden();
        $this->get(route('admin.finance.balance-sheet.csv', ['period_id' => $this->period->id]))->assertForbidden();

        [$otherBusiness, $otherUser] = $this->otherTenant();
        $foreignPeriod = AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Foreign period',
            'financial_year' => 2024,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $otherUser->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.finance.balance-sheet.csv', ['period_id' => $foreignPeriod->id]))
            ->assertSessionHasErrors('period_id');
        $this->get(route('admin.finance.balance-sheet.csv', [
            'period_id' => $this->period->id,
            'comparison_period_id' => $this->period->id,
        ]))->assertSessionHasErrors('comparison_period_id');
    }

    private function createPeriod(string $name, int $year, string $from, string $to): AccountingPeriod
    {
        return AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => $name,
            'financial_year' => $year,
            'start_date' => $from,
            'end_date' => $to,
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->user->id,
        ]);
    }

    /** @param list<array<string, mixed>> $lines */
    private function postJournal(string $date, string $key, array $lines): JournalEntry
    {
        return app(PostJournalEntryAction::class)->execute($this->business, $this->user, [
            'entry_date' => $date,
            'description' => 'Balance sheet test journal',
            'idempotency_key' => 'balance-sheet:'.$key,
            'source_snapshot' => ['test' => true],
            'lines' => $lines,
        ]);
    }

    /** @return array<string, mixed> */
    private function line(string $code, string $debit, string $credit): array
    {
        return [
            'ledger_account_id' => $this->account($code)->id,
            'debit_gbp' => $debit,
            'credit_gbp' => $credit,
        ];
    }

    private function account(string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    /** @return array{Business, User} */
    private function otherTenant(): array
    {
        $business = Business::create([
            'name' => 'Private Balance Sheet Business',
            'slug' => 'private-balance-sheet-'.str()->lower(str()->random(6)),
            'is_active' => true,
        ]);
        $user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        app(InitializeAccountingSetupAction::class)->execute($business, $user);

        return [$business, $user];
    }
}
