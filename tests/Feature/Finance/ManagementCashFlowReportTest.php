<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\ReverseJournalEntryAction;
use App\Filament\Pages\ManagementCashFlowPage;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Services\Finance\ManagementCashFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ManagementCashFlowReportTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private AccountingPeriod $openingPeriod;

    private AccountingPeriod $comparisonPeriod;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Management Cash Flow Test Business',
            'slug' => 'management-cash-flow-test',
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

        $this->openingPeriod = $this->createPeriod('FY 2024', 2024, '2024-01-01', '2024-12-31');
        $this->comparisonPeriod = $this->createPeriod('FY 2025', 2025, '2025-01-01', '2025-12-31');
        $this->period = $this->createPeriod('FY 2026', 2026, '2026-01-01', '2026-12-31');

        $this->createAccount('1110', 'Reserve cash', 'asset', 'cash', 'debit');
        $this->createAccount('2500', 'Business loan', 'liability', 'loan', 'credit');
    }

    public function test_cash_flow_reconciles_sections_comparison_transfers_and_reversals_in_pennies(): void
    {
        $this->postJournal('2024-12-01', 'opening-capital', [
            $this->line('1100', '1000.00', '0.00'),
            $this->line('3100', '0.00', '1000.00'),
        ]);

        $this->postJournal('2025-03-01', 'comparison-sale', [
            $this->line('1100', '200.00', '0.00'),
            $this->line('4100', '0.00', '200.00'),
        ]);
        $this->postJournal('2025-04-01', 'comparison-expense', [
            $this->line('6200', '50.00', '0.00'),
            $this->line('1100', '0.00', '50.00'),
        ]);
        $this->postJournal('2025-05-01', 'comparison-equipment', [
            $this->line('1500', '100.00', '0.00'),
            $this->line('1100', '0.00', '100.00'),
        ]);
        $this->postJournal('2025-06-01', 'comparison-share-capital', [
            $this->line('1100', '300.00', '0.00'),
            $this->line('3100', '0.00', '300.00'),
        ]);
        $this->postJournal('2025-07-01', 'comparison-transfer', [
            $this->line('1110', '40.00', '0.00'),
            $this->line('1100', '0.00', '40.00'),
        ]);

        $this->postJournal('2026-02-01', 'customer-receipt', [
            $this->line('1100', '400.00', '0.00'),
            $this->line('1200', '0.00', '400.00'),
        ]);
        $this->postJournal('2026-03-01', 'supplier-payment', [
            $this->line('2100', '120.00', '0.00'),
            $this->line('1100', '0.00', '120.00'),
        ]);
        $this->postJournal('2026-04-01', 'equipment-purchase', [
            $this->line('1500', '250.00', '0.00'),
            $this->line('1100', '0.00', '250.00'),
        ]);
        $this->postJournal('2026-05-01', 'loan-receipt', [
            $this->line('1100', '200.00', '0.00'),
            $this->line('2500', '0.00', '200.00'),
        ]);
        $mistake = $this->postJournal('2026-06-01', 'mistaken-cash-sale', [
            $this->line('1100', '25.00', '0.00'),
            $this->line('4100', '0.00', '25.00'),
        ]);
        app(ReverseJournalEntryAction::class)->execute($mistake, $this->user, 'Remove cash flow test error', '2026-06-02');
        $this->postJournal('2026-07-01', 'current-transfer', [
            $this->line('1110', '60.00', '0.00'),
            $this->line('1100', '0.00', '60.00'),
        ]);
        $this->account('1500')->update(['is_active' => false]);

        $report = app(ManagementCashFlowService::class)->report(
            $this->business->id,
            $this->period->id,
            $this->comparisonPeriod->id,
        );
        $rows = $report['sections']->flatMap(fn (object $section) => $section->rows)->keyBy('code');
        $sections = $report['sections']->keyBy('key');

        $this->assertSame('FY 2026', $report['period']->name);
        $this->assertSame('FY 2025', $report['comparison_period']->name);
        $this->assertSame(400.0, $rows['1200']->amount_gbp);
        $this->assertSame(-120.0, $rows['2100']->amount_gbp);
        $this->assertSame(-250.0, $rows['1500']->amount_gbp);
        $this->assertSame(-100.0, $rows['1500']->comparison_gbp);
        $this->assertSame(-150.0, $rows['1500']->variance_gbp);
        $this->assertFalse($rows['1500']->is_active);
        $this->assertSame(200.0, $rows['2500']->amount_gbp);
        $this->assertSame(0.0, $rows['4100']->amount_gbp);
        $this->assertSame(200.0, $rows['4100']->comparison_gbp);
        $this->assertArrayNotHasKey('1100', $rows->all());
        $this->assertArrayNotHasKey('1110', $rows->all());
        $this->assertSame(280.0, $sections['operating']->amount_gbp);
        $this->assertSame(150.0, $sections['operating']->comparison_gbp);
        $this->assertSame(-250.0, $sections['investing']->amount_gbp);
        $this->assertSame(200.0, $sections['financing']->amount_gbp);
        $this->assertSame(1350.0, $report['totals']->opening_cash_gbp);
        $this->assertSame(1000.0, $report['totals']->comparison_opening_cash_gbp);
        $this->assertSame(625.0, $report['totals']->cash_inflows_gbp);
        $this->assertSame(395.0, $report['totals']->cash_outflows_gbp);
        $this->assertSame(230.0, $report['totals']->net_cash_flow_gbp);
        $this->assertSame(350.0, $report['totals']->comparison_net_cash_flow_gbp);
        $this->assertSame(-120.0, $report['totals']->variance_net_cash_flow_gbp);
        $this->assertSame(1580.0, $report['totals']->closing_cash_gbp);
        $this->assertSame(0.0, $report['totals']->difference_gbp);
        $this->assertTrue($report['totals']->is_reconciled);
        $this->assertTrue($report['totals']->comparison_is_reconciled);
        $this->assertSame(7, $report['totals']->account_count);
        $this->assertSame(2, $report['totals']->cash_account_count);
        $this->assertSame(7, $report['totals']->journal_count);
    }

    public function test_empty_period_has_zero_flows_and_reconciles_cash_balances(): void
    {
        $report = app(ManagementCashFlowService::class)->report($this->business->id, $this->period->id);

        $this->assertSame(0.0, $report['totals']->opening_cash_gbp);
        $this->assertSame(0.0, $report['totals']->cash_inflows_gbp);
        $this->assertSame(0.0, $report['totals']->cash_outflows_gbp);
        $this->assertSame(0.0, $report['totals']->net_cash_flow_gbp);
        $this->assertSame(0.0, $report['totals']->closing_cash_gbp);
        $this->assertSame(0.0, $report['totals']->difference_gbp);
        $this->assertTrue($report['totals']->is_reconciled);
        $this->assertNull($report['totals']->comparison_difference_gbp);
        $this->assertNull($report['totals']->comparison_is_reconciled);
        $this->assertSame(0, $report['totals']->account_count);
        $this->assertSame(2, $report['totals']->cash_account_count);
        $this->assertSame(0, $report['totals']->journal_count);
        $this->assertTrue($report['sections']->every(fn (object $section): bool => $section->rows->isEmpty()));
    }

    public function test_service_rejects_foreign_and_identical_comparison_periods(): void
    {
        try {
            app(ManagementCashFlowService::class)->report(
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
        app(ManagementCashFlowService::class)->report($this->business->id, $foreignPeriod->id);
    }

    public function test_authorized_user_can_open_page_and_export_tenant_csv(): void
    {
        $this->postJournal('2026-07-01', 'export-receipt', [
            $this->line('1100', '75.00', '0.00'),
            $this->line('1200', '0.00', '75.00'),
        ]);
        [$otherBusiness, $otherUser] = $this->otherTenant();
        AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Private comparison period',
            'financial_year' => 2023,
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $otherUser->id,
        ]);

        $this->actingAs($this->user)
            ->get(ManagementCashFlowPage::getUrl())
            ->assertOk()
            ->assertSee(__('finance.management_cash_flow.title'))
            ->assertSee('FY 2026')
            ->assertDontSee('Private comparison period');

        $response = $this->get(route('admin.finance.management-cash-flow.csv', [
            'period_id' => $this->period->id,
            'comparison_period_id' => $this->comparisonPeriod->id,
        ]));
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('cache-control', 'no-store, private');
        $content = $response->streamedContent();

        $this->assertStringContainsString('MANAGEMENT CASH FLOW', $content);
        $this->assertStringContainsString('FY 2026', $content);
        $this->assertStringContainsString('1200', $content);
        $this->assertStringContainsString('Net change in cash', $content);
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

        $this->actingAs($member)->get(ManagementCashFlowPage::getUrl())->assertForbidden();
        $this->get(route('admin.finance.management-cash-flow.csv', ['period_id' => $this->period->id]))->assertForbidden();

        [$otherBusiness, $otherUser] = $this->otherTenant();
        $foreignPeriod = AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Foreign period',
            'financial_year' => 2023,
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $otherUser->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('admin.finance.management-cash-flow.csv', ['period_id' => $foreignPeriod->id]))
            ->assertSessionHasErrors('period_id');
        $this->get(route('admin.finance.management-cash-flow.csv', [
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

    private function createAccount(string $code, string $name, string $type, string $subtype, string $normalBalance): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'code' => $code,
            'name' => ['en' => $name, 'pl' => $name, 'pt' => $name],
            'type' => $type,
            'subtype' => $subtype,
            'normal_balance' => $normalBalance,
            'is_postable' => true,
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    /** @param list<array<string, mixed>> $lines */
    private function postJournal(string $date, string $key, array $lines): JournalEntry
    {
        return app(PostJournalEntryAction::class)->execute($this->business, $this->user, [
            'entry_date' => $date,
            'description' => 'Management cash flow test journal',
            'idempotency_key' => 'management-cash-flow:'.$key,
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
            'name' => 'Private Management Cash Flow Business',
            'slug' => 'private-management-cash-flow-'.str()->lower(str()->random(6)),
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
