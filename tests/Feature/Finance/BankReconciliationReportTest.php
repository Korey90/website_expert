<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateBankAccountAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Filament\Pages\BankReconciliationReportPage;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAllocation;
use App\Models\BankTransaction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Services\Finance\BankReconciliationReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BankReconciliationReportTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private BankAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Bank Report Ltd',
            'slug' => 'bank-report',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create(['is_active' => true]);
        $this->addMember($this->business, $this->user);

        foreach (['access_admin_panel', 'view_finance', 'manage_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance']);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $this->account = $this->createAccount(
            $this->business,
            $this->user,
            'Operating account',
            '12345678',
            'GBP',
            1000,
            '2026-01-01',
        );
    }

    public function test_report_calculates_balances_and_active_reconciliation_coverage(): void
    {
        $this->transaction($this->business, $this->account, '2026-01-05', 200, 'OPENING-MOVE');
        $receipt = $this->transaction($this->business, $this->account, '2026-02-01', 300, 'RECEIPT-300');
        $payment = $this->transaction($this->business, $this->account, '2026-02-02', -100, 'PAYMENT-100');
        $unmatched = $this->transaction($this->business, $this->account, '2026-02-03', -50, 'PAYMENT-50');
        $this->transaction($this->business, $this->account, '2026-03-01', 999, 'OUTSIDE-PERIOD');
        $this->allocation($receipt, 300);
        $this->allocation($payment, 40);
        $this->allocation($payment, 60, reversed: true);

        $report = app(BankReconciliationReportService::class)->report(
            $this->business->id,
            '2026-02-01',
            '2026-02-28',
        );
        $account = $report['accounts']->sole();

        $this->assertSame(1200.0, $account->opening_balance);
        $this->assertSame(300.0, $account->inflows);
        $this->assertSame(150.0, $account->outflows);
        $this->assertSame(1350.0, $account->closing_balance);
        $this->assertSame(340.0, $account->allocated_amount);
        $this->assertSame(110.0, $account->remaining_amount);
        $this->assertSame(75.6, $account->reconciliation_rate);
        $this->assertSame(3, $account->transaction_count);
        $this->assertSame(1, $account->reconciled_count);
        $this->assertSame(1, $account->partial_count);
        $this->assertSame(1, $account->unmatched_count);
        $this->assertSame(BankTransaction::STATUS_PARTIALLY_RECONCILED, $report['transactions']->firstWhere('id', $payment->id)->status);
        $this->assertSame(60.0, $report['transactions']->firstWhere('id', $payment->id)->remaining_amount);
        $this->assertSame(BankTransaction::STATUS_UNMATCHED, $report['transactions']->firstWhere('id', $unmatched->id)->status);
        $this->assertSame(2, $report['totals']->open_count);
    }

    public function test_report_keeps_currencies_separate_and_filters_one_account(): void
    {
        $euroAccount = $this->createAccount(
            $this->business,
            $this->user,
            'Euro account',
            '87654321',
            'EUR',
            500,
            '2026-01-01',
        );
        $this->transaction($this->business, $this->account, '2026-02-01', 100, 'GBP-100');
        $this->transaction($this->business, $euroAccount, '2026-02-01', 50, 'EUR-50', 'EUR');

        $all = app(BankReconciliationReportService::class)->report(
            $this->business->id,
            '2026-02-01',
            '2026-02-28',
        );
        $filtered = app(BankReconciliationReportService::class)->report(
            $this->business->id,
            '2026-02-01',
            '2026-02-28',
            $euroAccount->id,
        );

        $this->assertCount(2, $all['accounts']);
        $this->assertObjectNotHasProperty('closing_balance', $all['totals']);
        $this->assertSame('EUR', $filtered['accounts']->sole()->currency);
        $this->assertSame(550.0, $filtered['accounts']->sole()->closing_balance);
        $this->assertSame('EUR-50', $filtered['transactions']->sole()->reference);
    }

    public function test_report_rejects_foreign_account_and_excludes_foreign_transactions(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Bank Report Ltd',
            'slug' => 'other-bank-report',
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create(['is_active' => true]);
        $this->addMember($otherBusiness, $otherUser);
        $otherUser->givePermissionTo('manage_finance');
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness, $otherUser);
        $otherAccount = $this->createAccount(
            $otherBusiness,
            $otherUser,
            'Private account',
            '22223333',
            'GBP',
            9000,
            '2026-01-01',
        );
        $this->transaction($otherBusiness, $otherAccount, '2026-02-01', 9000, 'PRIVATE');

        $report = app(BankReconciliationReportService::class)->report(
            $this->business->id,
            '2026-02-01',
            '2026-02-28',
        );

        $this->assertCount(1, $report['accounts']);
        $this->assertTrue($report['transactions']->isEmpty());

        $this->expectException(InvalidArgumentException::class);
        app(BankReconciliationReportService::class)->report(
            $this->business->id,
            '2026-02-01',
            '2026-02-28',
            $otherAccount->id,
        );
    }

    public function test_authorized_user_can_open_report_and_export_tenant_csv(): void
    {
        $this->transaction($this->business, $this->account, today()->toDateString(), 125, 'CSV-REPORT-125');
        $this->actingAs($this->user);

        $this->get(BankReconciliationReportPage::getUrl())
            ->assertOk()
            ->assertSee(__('finance.bank_reconciliation_report.title'))
            ->assertSee('CSV-REPORT-125');
        $response = $this->get(route('admin.finance.bank-reconciliation-report.csv', [
            'date_from' => today()->startOfMonth()->toDateString(),
            'date_to' => today()->toDateString(),
            'account_id' => $this->account->id,
        ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('CSV-REPORT-125', $csv);
        $this->assertStringContainsString('Operating account', $csv);
    }

    public function test_user_without_finance_permission_cannot_export_report(): void
    {
        $member = User::factory()->create(['is_active' => true]);
        $this->addMember($this->business, $member);

        $this->actingAs($member)
            ->get(route('admin.finance.bank-reconciliation-report.csv', [
                'date_from' => '2026-02-01',
                'date_to' => '2026-02-28',
            ]))
            ->assertForbidden();
    }

    private function createAccount(
        Business $business,
        User $user,
        string $name,
        string $accountNumber,
        string $currency,
        float $openingBalance,
        string $openingDate,
    ): BankAccount {
        $cash = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('code', '1100')
            ->firstOrFail();

        return app(CreateBankAccountAction::class)->execute($business, $user, [
            'name' => $name,
            'bank_name' => 'Example Bank',
            'account_number' => $accountNumber,
            'sort_code' => '12-34-56',
            'currency' => $currency,
            'ledger_account_id' => $cash->id,
            'opening_balance' => $openingBalance,
            'opening_balance_date' => $openingDate,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
    }

    private function transaction(
        Business $business,
        BankAccount $account,
        string $date,
        float $amount,
        string $reference,
        ?string $currency = null,
    ): BankTransaction {
        return BankTransaction::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'bank_account_id' => $account->id,
            'booking_date' => $date,
            'amount' => $amount,
            'currency' => $currency ?? $account->currency,
            'reference' => $reference,
            'counterparty' => 'Report Counterparty',
            'source_hash' => hash('sha256', $business->id.'|'.$reference.'|'.fake()->uuid()),
            'reconciliation_status' => BankTransaction::STATUS_UNMATCHED,
        ]);
    }

    private function allocation(BankTransaction $transaction, float $amount, bool $reversed = false): void
    {
        $reconciliation = BankReconciliation::withoutGlobalScopes()->create([
            'business_id' => $transaction->business_id,
            'bank_transaction_id' => $transaction->id,
            'created_by' => $this->user->id,
            'status' => BankReconciliation::STATUS_ACTIVE,
            'total_amount' => $amount,
            'currency' => $transaction->currency,
            'reconciled_at' => now(),
        ]);
        BankReconciliationAllocation::withoutGlobalScopes()->create([
            'business_id' => $transaction->business_id,
            'bank_reconciliation_id' => $reconciliation->id,
            'bank_transaction_id' => $transaction->id,
            'reconcilable_type' => User::class,
            'reconcilable_id' => $this->user->id,
            'event_type' => 'test_payment',
            'candidate_key' => 'test:'.fake()->uuid(),
            'amount' => $amount,
            'currency' => $transaction->currency,
            'source_amount' => $amount,
            'source_currency' => $transaction->currency,
            'match_score' => 100,
        ]);

        if ($reversed) {
            DB::table('bank_reconciliations')
                ->where('id', $reconciliation->id)
                ->update([
                    'status' => BankReconciliation::STATUS_REVERSED,
                    'reversed_by' => $this->user->id,
                    'reversed_at' => now(),
                    'reversal_reason' => 'Test reversal excluded from report coverage.',
                ]);
        }
    }

    private function addMember(Business $business, User $user): void
    {
        BusinessUser::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }
}
