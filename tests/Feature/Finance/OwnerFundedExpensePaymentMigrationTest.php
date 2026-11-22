<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Models\Business;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\User;
use App\Services\Finance\BankAccountDetailsProtector;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OwnerFundedExpensePaymentMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_backfills_existing_bank_and_cash_payments_and_creates_owner_capital(): void
    {
        $business = Business::create(['name' => 'Migration Books', 'slug' => 'migration-books', 'is_active' => true]);
        $user = User::factory()->create();
        app(InitializeAccountingSetupAction::class)->execute($business, $user);
        $expense = Expense::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'created_by' => $user->id,
            'status' => Expense::STATUS_POSTED,
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'total' => 20,
            'amount_paid' => 20,
            'amount_due' => 0,
        ]);
        $cashLedgerId = DB::table('ledger_accounts')
            ->where('business_id', $business->id)
            ->where('code', '1100')
            ->value('id');
        $protected = app(BankAccountDetailsProtector::class)->protect('12345678', '123456');
        $bankAccountId = DB::table('bank_accounts')->insertGetId([
            'business_id' => $business->id,
            'ledger_account_id' => $cashLedgerId,
            'name' => 'Migration bank',
            'bank_name' => 'Example Bank',
            ...$protected,
            'currency' => 'GBP',
            'opening_balance' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bankPaymentId = $this->insertPayment($business->id, $expense->id, $user->id, $bankAccountId, 'MIG-BANK');
        $cashPaymentId = $this->insertPayment($business->id, $expense->id, $user->id, null, 'MIG-CASH');

        /** @var Migration $migration */
        $migration = require database_path('migrations/2026_11_22_000036_add_owner_funded_expense_payments.php');
        $migration->down();
        $migration->up();

        $this->assertSame(
            ExpensePayment::SOURCE_BANK_ACCOUNT,
            DB::table('expense_payments')->where('id', $bankPaymentId)->value('settlement_source'),
        );
        $this->assertSame(
            ExpensePayment::SOURCE_CASH_ACCOUNT,
            DB::table('expense_payments')->where('id', $cashPaymentId)->value('settlement_source'),
        );
        $ownerCapital = DB::table('ledger_accounts')
            ->where('business_id', $business->id)
            ->where('code', '3300')
            ->first();
        $equityHeadingId = DB::table('ledger_accounts')
            ->where('business_id', $business->id)
            ->where('code', '3000')
            ->value('id');

        $this->assertNotNull($ownerCapital);
        $this->assertSame('owner_capital', $ownerCapital->subtype);
        $this->assertSame($equityHeadingId, $ownerCapital->parent_id);
    }

    private function insertPayment(
        string $businessId,
        int $expenseId,
        int $userId,
        ?int $bankAccountId,
        string $reference,
    ): int {
        return DB::table('expense_payments')->insertGetId([
            'business_id' => $businessId,
            'expense_id' => $expenseId,
            'bank_account_id' => $bankAccountId,
            'created_by' => $userId,
            'amount' => 10,
            'currency' => 'GBP',
            'amount_gbp' => 10,
            'carrying_amount_gbp' => 10,
            'realised_fx_gbp' => 0,
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-11-05',
            'method' => 'cash',
            'reference' => $reference,
            'paid_at' => '2026-11-05 09:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
