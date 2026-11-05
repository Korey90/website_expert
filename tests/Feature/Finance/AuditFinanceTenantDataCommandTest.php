<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Models\Business;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Finance\BankAccountDetailsProtector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditFinanceTenantDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_passes_when_finance_tenant_data_is_consistent(): void
    {
        $business = Business::create(['name' => 'Agency', 'slug' => 'agency', 'is_active' => true]);
        $client = Client::create([
            'business_id' => $business->id,
            'company_name' => 'Client Ltd',
            'status' => 'active',
        ]);

        Contact::create([
            'business_id' => $business->id,
            'client_id' => $client->id,
            'first_name' => 'Jamie',
            'last_name' => 'Jones',
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit passed.')
            ->assertExitCode(0);
    }

    public function test_audit_fails_and_reports_an_unassigned_record(): void
    {
        $business = Business::create(['name' => 'Agency', 'slug' => 'agency', 'is_active' => true]);
        $client = Client::create([
            'business_id' => $business->id,
            'company_name' => 'Client Ltd',
            'status' => 'active',
        ]);

        Contact::create([
            'business_id' => null,
            'client_id' => $client->id,
            'first_name' => 'Jamie',
            'last_name' => 'Jones',
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }

    public function test_supplierless_expense_is_valid_but_cross_tenant_supplier_is_reported(): void
    {
        $business = Business::create(['name' => 'Agency', 'slug' => 'expense-audit', 'is_active' => true]);
        $otherBusiness = Business::create(['name' => 'Other', 'slug' => 'expense-audit-other', 'is_active' => true]);
        $otherSupplier = Supplier::factory()->for($otherBusiness)->create();

        Expense::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'supplier_id' => null,
            'status' => Expense::STATUS_DRAFT,
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit passed.')
            ->assertExitCode(0);

        Expense::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'supplier_id' => $otherSupplier->id,
            'status' => Expense::STATUS_DRAFT,
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }

    public function test_cross_tenant_ledger_parent_is_reported(): void
    {
        $business = Business::create(['name' => 'Ledger Audit', 'slug' => 'ledger-audit', 'is_active' => true]);
        $otherBusiness = Business::create(['name' => 'Other Ledger Audit', 'slug' => 'other-ledger-audit', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($business);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness);
        $account = LedgerAccount::withoutGlobalScopes()->where('business_id', $business->id)->where('code', '6200')->firstOrFail();
        $foreignParent = LedgerAccount::withoutGlobalScopes()->where('business_id', $otherBusiness->id)->where('code', '5000')->firstOrFail();

        $account->update(['parent_id' => $foreignParent->id]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }

    public function test_cross_tenant_bank_ledger_account_is_reported(): void
    {
        $business = Business::create(['name' => 'Bank Audit', 'slug' => 'bank-audit', 'is_active' => true]);
        $otherBusiness = Business::create(['name' => 'Other Bank Audit', 'slug' => 'other-bank-audit', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($business);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness);
        $foreignCash = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $otherBusiness->id)
            ->where('code', '1100')
            ->firstOrFail();
        $protected = app(BankAccountDetailsProtector::class)->protect('12345678', '123456');

        DB::table('bank_accounts')->insert([
            'business_id' => $business->id,
            'ledger_account_id' => $foreignCash->id,
            'name' => 'Invalid bank',
            'bank_name' => 'Audit Bank',
            ...$protected,
            'currency' => 'GBP',
            'opening_balance' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }

    public function test_cross_tenant_bank_transaction_and_import_are_reported(): void
    {
        $business = Business::create(['name' => 'Transaction Audit', 'slug' => 'transaction-audit', 'is_active' => true]);
        $otherBusiness = Business::create(['name' => 'Other Transaction Audit', 'slug' => 'other-transaction-audit', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($business);
        $cash = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('code', '1100')
            ->firstOrFail();
        $protected = app(BankAccountDetailsProtector::class)->protect('87654321', '654321');
        $bankAccountId = DB::table('bank_accounts')->insertGetId([
            'business_id' => $business->id,
            'ledger_account_id' => $cash->id,
            'name' => 'Audit bank',
            'bank_name' => 'Audit Bank',
            ...$protected,
            'currency' => 'GBP',
            'opening_balance' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $importId = DB::table('bank_statement_imports')->insertGetId([
            'business_id' => $business->id,
            'bank_account_id' => $bankAccountId,
            'original_filename' => 'audit.csv',
            'file_hash' => str_repeat('a', 64),
            'status' => 'completed',
            'column_mapping' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('bank_transactions')->insert([
            'business_id' => $otherBusiness->id,
            'bank_account_id' => $bankAccountId,
            'bank_statement_import_id' => $importId,
            'booking_date' => '2026-11-05',
            'amount' => 10,
            'currency' => 'GBP',
            'source_hash' => str_repeat('b', 64),
            'reconciliation_status' => 'unmatched',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit failed: 2 invalid record(s).')
            ->assertExitCode(1);
    }

    public function test_cross_tenant_payment_bank_account_is_reported(): void
    {
        $business = Business::create(['name' => 'Payment Audit', 'slug' => 'payment-audit', 'is_active' => true]);
        $otherBusiness = Business::create(['name' => 'Other Payment Audit', 'slug' => 'other-payment-audit', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($business);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness);
        $client = Client::create([
            'business_id' => $business->id,
            'company_name' => 'Payment Audit Client',
            'status' => 'active',
        ]);
        $creator = User::factory()->create();
        $invoice = Invoice::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'number' => 'INV-PAYMENT-AUDIT',
            'client_id' => $client->id,
            'created_by' => $creator->id,
            'status' => 'sent',
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'due_date' => '2026-11-30',
        ]);
        $foreignCash = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $otherBusiness->id)
            ->where('code', '1100')
            ->firstOrFail();
        $protected = app(BankAccountDetailsProtector::class)->protect('22223333', '223344');
        $foreignBankAccountId = DB::table('bank_accounts')->insertGetId([
            'business_id' => $otherBusiness->id,
            'ledger_account_id' => $foreignCash->id,
            'name' => 'Foreign payment bank',
            'bank_name' => 'Audit Bank',
            ...$protected,
            'currency' => 'GBP',
            'opening_balance' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            'business_id' => $business->id,
            'invoice_id' => $invoice->id,
            'bank_account_id' => $foreignBankAccountId,
            'amount' => 10,
            'amount_gbp' => 10,
            'currency' => 'GBP',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-11-05',
            'method' => 'bank_transfer',
            'status' => 'completed',
            'paid_at' => '2026-11-05 09:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('payments.bank_account_id')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }

    public function test_missing_polymorphic_reconciliation_source_is_reported(): void
    {
        $business = Business::create(['name' => 'Reconciliation Audit', 'slug' => 'reconciliation-audit', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($business);
        $cash = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('code', '1100')
            ->firstOrFail();
        $protected = app(BankAccountDetailsProtector::class)->protect('11112222', '112233');
        $bankAccountId = DB::table('bank_accounts')->insertGetId([
            'business_id' => $business->id,
            'ledger_account_id' => $cash->id,
            'name' => 'Reconciliation audit bank',
            'bank_name' => 'Audit Bank',
            ...$protected,
            'currency' => 'GBP',
            'opening_balance' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $transactionId = DB::table('bank_transactions')->insertGetId([
            'business_id' => $business->id,
            'bank_account_id' => $bankAccountId,
            'booking_date' => '2026-11-05',
            'amount' => 25,
            'currency' => 'GBP',
            'source_hash' => str_repeat('c', 64),
            'reconciliation_status' => 'reconciled',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $reconciliationId = DB::table('bank_reconciliations')->insertGetId([
            'business_id' => $business->id,
            'bank_transaction_id' => $transactionId,
            'status' => 'active',
            'total_amount' => 25,
            'currency' => 'GBP',
            'reconciled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('bank_reconciliation_allocations')->insert([
            'business_id' => $business->id,
            'bank_reconciliation_id' => $reconciliationId,
            'bank_transaction_id' => $transactionId,
            'reconcilable_type' => Payment::class,
            'reconcilable_id' => 999999,
            'event_type' => 'customer_payment',
            'candidate_key' => 'payment:999999:receipt',
            'amount' => 25,
            'currency' => 'GBP',
            'source_amount' => 25,
            'source_currency' => 'GBP',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('bank_reconciliation_allocations.source')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }
}
