<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\ReverseJournalEntryAction;
use App\Filament\Pages\ProfitAndLossPage;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Services\Finance\ProfitAndLossService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProfitAndLossReportTest extends TestCase
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
            'name' => 'P and L Test Business',
            'slug' => 'p-and-l-test',
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

    public function test_profit_and_loss_calculates_sections_metrics_comparison_and_variance_in_pennies(): void
    {
        $this->postProfitAndLossSet('2025-06-01', 'comparison', [
            'sales' => '900.00',
            'cost_of_sales' => '180.00',
            'operating_expenses' => '90.00',
            'other_income' => '8.00',
            'other_expenses' => '4.00',
            'corporation_tax' => '40.00',
        ]);
        $this->postProfitAndLossSet('2026-06-01', 'current', [
            'sales' => '1000.01',
            'cost_of_sales' => '200.02',
            'operating_expenses' => '100.03',
            'other_income' => '10.04',
            'other_expenses' => '5.05',
            'corporation_tax' => '50.06',
        ]);
        $this->account('6200')->update(['is_active' => false]);
        $this->postJournal('2026-07-01', 'balance-sheet-only', [
            $this->line('1100', '25.00', '0.00'),
            $this->line('3100', '0.00', '25.00'),
        ]);

        $report = app(ProfitAndLossService::class)->report(
            $this->business->id,
            $this->period->id,
            $this->comparisonPeriod->id,
        );
        $rows = $report['sections']->flatMap(fn (object $section) => $section->rows)->keyBy('code');

        $this->assertSame('FY 2026', $report['period']->name);
        $this->assertSame('FY 2025', $report['comparison_period']->name);
        $this->assertSame(1000.01, $rows['4100']->amount_gbp);
        $this->assertSame(900.0, $rows['4100']->comparison_gbp);
        $this->assertSame(100.01, $rows['4100']->variance_gbp);
        $this->assertSame(100.03, $rows['6200']->amount_gbp);
        $this->assertFalse($rows['6200']->is_active);
        $this->assertArrayNotHasKey('1100', $rows->all());
        $this->assertSame(799.99, $report['totals']->gross_profit_gbp);
        $this->assertSame(699.96, $report['totals']->operating_profit_gbp);
        $this->assertSame(704.95, $report['totals']->profit_before_tax_gbp);
        $this->assertSame(654.89, $report['totals']->net_profit_gbp);
        $this->assertSame(594.0, $report['totals']->comparison_net_profit_gbp);
        $this->assertSame(60.89, $report['totals']->variance_net_profit_gbp);
        $this->assertSame(6, $report['totals']->account_count);
        $this->assertSame(6, $report['totals']->journal_count);
    }

    public function test_corrections_can_produce_negative_income_and_expense_values(): void
    {
        $this->postJournal('2026-03-01', 'negative-sales', [
            $this->line('4100', '20.01', '0.00'),
            $this->line('1100', '0.00', '20.01'),
        ]);
        $this->postJournal('2026-03-02', 'negative-expense', [
            $this->line('1100', '5.02', '0.00'),
            $this->line('6200', '0.00', '5.02'),
        ]);

        $report = app(ProfitAndLossService::class)->report($this->business->id, $this->period->id);

        $this->assertSame(-20.01, $report['totals']->sales_gbp);
        $this->assertSame(-5.02, $report['totals']->operating_expenses_gbp);
        $this->assertSame(-14.99, $report['totals']->net_profit_gbp);
    }

    public function test_reversed_journal_remains_visible_and_nets_to_zero(): void
    {
        $original = $this->postJournal('2026-05-01', 'p-and-l-mistake', [
            $this->line('1100', '12.34', '0.00'),
            $this->line('4100', '0.00', '12.34'),
        ]);
        app(ReverseJournalEntryAction::class)->execute($original, $this->user, 'Remove profit and loss test error', '2026-05-02');

        $report = app(ProfitAndLossService::class)->report($this->business->id, $this->period->id);
        $sales = $report['sections']->firstWhere('key', 'sales');

        $this->assertCount(1, $sales->rows);
        $this->assertSame(0.0, $sales->rows->first()->amount_gbp);
        $this->assertSame(0.0, $report['totals']->net_profit_gbp);
        $this->assertSame(2, $report['totals']->journal_count);
    }

    public function test_empty_period_has_zero_metrics_and_no_accounts(): void
    {
        $report = app(ProfitAndLossService::class)->report($this->business->id, $this->period->id);

        $this->assertSame(0.0, $report['totals']->sales_gbp);
        $this->assertSame(0.0, $report['totals']->net_profit_gbp);
        $this->assertNull($report['totals']->comparison_net_profit_gbp);
        $this->assertSame(0, $report['totals']->account_count);
        $this->assertTrue($report['sections']->every(fn (object $section): bool => $section->rows->isEmpty()));
    }

    public function test_service_rejects_foreign_and_identical_comparison_periods(): void
    {
        try {
            app(ProfitAndLossService::class)->report(
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
        app(ProfitAndLossService::class)->report($this->business->id, $foreignPeriod->id);
    }

    public function test_authorized_user_can_open_page_and_export_tenant_csv(): void
    {
        $this->postJournal('2026-07-01', 'p-and-l-export', [
            $this->line('1100', '75.00', '0.00'),
            $this->line('4100', '0.00', '75.00'),
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
            ->get(ProfitAndLossPage::getUrl())
            ->assertOk()
            ->assertSee(__('finance.profit_and_loss.title'))
            ->assertSee('FY 2026')
            ->assertDontSee('Private comparison period');

        $response = $this->get(route('admin.finance.profit-and-loss.csv', [
            'period_id' => $this->period->id,
            'comparison_period_id' => $this->comparisonPeriod->id,
        ]));
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('cache-control', 'no-store, private');
        $content = $response->streamedContent();

        $this->assertStringContainsString('PROFIT AND LOSS', $content);
        $this->assertStringContainsString('FY 2026', $content);
        $this->assertStringContainsString('4100', $content);
        $this->assertStringContainsString('Net profit / loss', $content);
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

        $this->actingAs($member)->get(ProfitAndLossPage::getUrl())->assertForbidden();
        $this->get(route('admin.finance.profit-and-loss.csv', ['period_id' => $this->period->id]))->assertForbidden();

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
            ->get(route('admin.finance.profit-and-loss.csv', ['period_id' => $foreignPeriod->id]))
            ->assertSessionHasErrors('period_id');
        $this->get(route('admin.finance.profit-and-loss.csv', [
            'period_id' => $this->period->id,
            'comparison_period_id' => $this->period->id,
        ]))->assertSessionHasErrors('comparison_period_id');
    }

    /** @param array{sales: string, cost_of_sales: string, operating_expenses: string, other_income: string, other_expenses: string, corporation_tax: string} $amounts */
    private function postProfitAndLossSet(string $date, string $key, array $amounts): void
    {
        $accounts = [
            'sales' => ['4100', 'credit'],
            'cost_of_sales' => ['5100', 'debit'],
            'operating_expenses' => ['6200', 'debit'],
            'other_income' => ['4200', 'credit'],
            'other_expenses' => ['7100', 'debit'],
            'corporation_tax' => ['7200', 'debit'],
        ];

        foreach ($accounts as $section => [$code, $side]) {
            $amount = $amounts[$section];
            $lines = $side === 'credit'
                ? [$this->line('1100', $amount, '0.00'), $this->line($code, '0.00', $amount)]
                : [$this->line($code, $amount, '0.00'), $this->line('1100', '0.00', $amount)];
            $this->postJournal($date, $key.':'.$section, $lines);
        }
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
            'description' => 'Profit and loss test journal',
            'idempotency_key' => 'p-and-l:'.$key,
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
            'name' => 'Private P and L Business',
            'slug' => 'private-p-and-l-'.str()->lower(str()->random(6)),
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
