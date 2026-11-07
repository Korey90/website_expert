<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\ReverseJournalEntryAction;
use App\Filament\Pages\TrialBalancePage;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Services\Finance\TrialBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TrialBalanceReportTest extends TestCase
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
            'name' => 'Trial Balance Ltd',
            'slug' => 'trial-balance',
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

    public function test_trial_balance_calculates_opening_activity_closing_comparison_and_variance_in_pennies(): void
    {
        $this->postJournal('2025-06-01', 'tb:opening', [
            $this->line('1100', '100.01', '0.00'),
            $this->line('3100', '0.00', '100.01'),
        ]);
        $this->postJournal('2026-03-01', 'tb:sale', [
            $this->line('1100', '50.02', '0.00'),
            $this->line('4100', '0.00', '50.02'),
        ]);
        $this->postJournal('2026-04-01', 'tb:expense', [
            $this->line('6200', '20.03', '0.00'),
            $this->line('1100', '0.00', '20.03'),
        ]);
        $this->account('6200')->update(['is_active' => false]);

        $report = app(TrialBalanceService::class)->report(
            $this->business->id,
            $this->period->id,
            $this->comparisonPeriod->id,
        );
        $rows = $report['rows']->keyBy('code');

        $this->assertSame('FY 2026', $report['period']->name);
        $this->assertSame('FY 2025', $report['comparison_period']->name);
        $this->assertSame(100.01, $rows['1100']->opening_debit_gbp);
        $this->assertSame(50.02, $rows['1100']->period_debit_gbp);
        $this->assertSame(20.03, $rows['1100']->period_credit_gbp);
        $this->assertSame(130.0, $rows['1100']->closing_debit_gbp);
        $this->assertSame(100.01, $rows['1100']->comparison_debit_gbp);
        $this->assertSame(29.99, $rows['1100']->variance_debit_gbp);
        $this->assertSame(50.02, $rows['4100']->variance_credit_gbp);
        $this->assertFalse($rows['6200']->is_active);
        $this->assertSame(100.01, $report['totals']->opening_debit_gbp);
        $this->assertSame(100.01, $report['totals']->opening_credit_gbp);
        $this->assertSame(70.05, $report['totals']->period_debit_gbp);
        $this->assertSame(70.05, $report['totals']->period_credit_gbp);
        $this->assertSame(150.03, $report['totals']->closing_debit_gbp);
        $this->assertSame(150.03, $report['totals']->closing_credit_gbp);
        $this->assertSame(50.02, $report['totals']->variance_debit_gbp);
        $this->assertSame(50.02, $report['totals']->variance_credit_gbp);
        $this->assertSame(0.0, $report['totals']->closing_difference_gbp);
        $this->assertTrue($report['totals']->is_balanced);
        $this->assertSame(4, $report['totals']->account_count);
        $this->assertSame(2, $report['totals']->journal_count);
    }

    public function test_reversed_journal_remains_in_activity_and_nets_the_closing_balance_to_zero(): void
    {
        $original = $this->postJournal('2026-05-01', 'tb:mistake', [
            $this->line('1100', '12.34', '0.00'),
            $this->line('3100', '0.00', '12.34'),
        ]);
        app(ReverseJournalEntryAction::class)->execute($original, $this->user, 'Remove trial balance test error', '2026-05-02');

        $report = app(TrialBalanceService::class)->report($this->business->id, $this->period->id);

        $this->assertCount(2, $report['rows']);
        $this->assertSame(24.68, $report['totals']->period_debit_gbp);
        $this->assertSame(24.68, $report['totals']->period_credit_gbp);
        $this->assertSame(0.0, $report['totals']->closing_debit_gbp);
        $this->assertSame(0.0, $report['totals']->closing_credit_gbp);
        $this->assertSame(2, $report['totals']->journal_count);
        $this->assertTrue($report['totals']->is_balanced);
    }

    public function test_empty_period_is_balanced_and_has_no_rows(): void
    {
        $report = app(TrialBalanceService::class)->report($this->business->id, $this->period->id);

        $this->assertTrue($report['rows']->isEmpty());
        $this->assertSame(0.0, $report['totals']->closing_debit_gbp);
        $this->assertSame(0.0, $report['totals']->closing_credit_gbp);
        $this->assertNull($report['totals']->comparison_debit_gbp);
        $this->assertTrue($report['totals']->is_balanced);
    }

    public function test_service_rejects_foreign_and_identical_comparison_periods(): void
    {
        try {
            app(TrialBalanceService::class)->report(
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
        app(TrialBalanceService::class)->report($this->business->id, $foreignPeriod->id);
    }

    public function test_authorized_user_can_open_page_and_export_tenant_csv(): void
    {
        $this->postJournal('2026-07-01', 'tb:export', [
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
            ->get(TrialBalancePage::getUrl())
            ->assertOk()
            ->assertSee(__('finance.trial_balance.title'))
            ->assertSee('FY 2026')
            ->assertDontSee('Private comparison period');

        $response = $this->get(route('admin.finance.trial-balance.csv', [
            'period_id' => $this->period->id,
            'comparison_period_id' => $this->comparisonPeriod->id,
        ]));
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('cache-control', 'no-store, private');
        $content = $response->streamedContent();

        $this->assertStringContainsString('TRIAL BALANCE', $content);
        $this->assertStringContainsString('FY 2026', $content);
        $this->assertStringContainsString('1100', $content);
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

        $this->actingAs($member)->get(TrialBalancePage::getUrl())->assertForbidden();
        $this->get(route('admin.finance.trial-balance.csv', ['period_id' => $this->period->id]))->assertForbidden();

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
            ->get(route('admin.finance.trial-balance.csv', ['period_id' => $foreignPeriod->id]))
            ->assertSessionHasErrors('period_id');
        $this->get(route('admin.finance.trial-balance.csv', [
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
            'description' => 'Trial balance test journal',
            'idempotency_key' => $key,
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
            'name' => 'Private Trial Balance Ltd',
            'slug' => 'private-trial-balance-'.str()->lower(str()->random(6)),
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
