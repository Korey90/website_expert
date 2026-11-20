<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateBankAccountAction;
use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\CreateRecordFromBankTransactionAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\IssueInvoiceAction;
use App\Actions\Finance\LinkExistingExpenseToBankTransactionAction;
use App\Filament\Resources\BankTransactionResource;
use App\Models\AccountingPeriod;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BankTransactionRecordCreationTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private BankAccount $bankAccount;

    private LedgerAccount $bankLedger;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Bank Creation Ltd',
            'slug' => 'bank-creation',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create(['is_active' => true]);
        $this->addMember($this->business, $this->user);

        foreach (['access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance', 'post_finance']);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $this->period = AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => 'FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->user->id,
        ]);
        $this->bankLedger = LedgerAccount::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'code' => '1110',
            'name' => ['en' => 'Operating bank', 'pl' => 'Rachunek operacyjny', 'pt' => 'Conta operacional'],
            'type' => 'asset',
            'subtype' => 'cash',
            'normal_balance' => 'debit',
            'is_postable' => true,
            'is_system' => false,
            'is_active' => true,
        ]);
        $this->bankAccount = app(CreateBankAccountAction::class)->execute($this->business, $this->user, [
            'name' => 'Operating account',
            'bank_name' => 'Example Bank',
            'account_number' => '12345678',
            'sort_code' => '12-34-56',
            'currency' => 'GBP',
            'ledger_account_id' => $this->bankLedger->id,
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
    }

    public function test_inflow_creates_posts_and_reconciles_customer_payment_on_transaction_bank_ledger(): void
    {
        $invoice = $this->issuedInvoice();
        $transaction = $this->transaction(120, 'CUSTOMER-RECEIPT', 'Customer Ltd');

        $reconciliation = $this->createRecord($transaction, ['invoice_id' => $invoice->id]);

        $payment = Payment::withoutGlobalScopes()->sole();
        $bankLine = $payment->journalEntries()->firstOrFail()->lines()
            ->where('ledger_account_id', $this->bankLedger->id)
            ->firstOrFail();

        $this->assertSame($this->bankAccount->id, $payment->bank_account_id);
        $this->assertSame('120.00', $payment->amount);
        $this->assertSame('120.00', $bankLine->debit_gbp);
        $this->assertSame('0.00', $bankLine->credit_gbp);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(BankTransaction::STATUS_RECONCILED, $transaction->fresh()->reconciliation_status);
        $this->assertSame($payment->id, $reconciliation->allocations->sole()->reconcilable_id);
    }

    public function test_outflow_creates_vat_expense_payment_posting_and_reconciliation(): void
    {
        $supplier = Supplier::factory()->for($this->business)->create();
        $transaction = $this->transaction(-120, 'SUPPLIER-PAYMENT', 'Supplier Ltd');

        $reconciliation = $this->createRecord($transaction, [
            'supplier_id' => $supplier->id,
            'supplier_invoice_number' => 'SUP-2026-001',
            'source_amount' => '120.00',
            'tax_code_id' => $this->taxCode('S20')->id,
            'ledger_account_id' => $this->expenseLedger()->id,
            'category' => 'software',
            'description' => 'Software subscription',
            'business_purpose' => 'Software used to deliver client projects',
        ]);

        $expense = Expense::withoutGlobalScopes()->with('items')->sole();
        $payment = ExpensePayment::withoutGlobalScopes()->sole();
        $bankLine = $payment->journalEntries()->firstOrFail()->lines()
            ->where('ledger_account_id', $this->bankLedger->id)
            ->firstOrFail();

        $this->assertSame(Expense::STATUS_PAID, $expense->status);
        $this->assertSame('100.00', $expense->subtotal);
        $this->assertSame('20.00', $expense->vat_amount);
        $this->assertSame('120.00', $expense->total);
        $this->assertSame('100.00', $expense->items->sole()->net_amount);
        $this->assertSame($this->bankAccount->id, $payment->bank_account_id);
        $this->assertSame('120.00', $bankLine->credit_gbp);
        $this->assertSame(BankTransaction::STATUS_RECONCILED, $transaction->fresh()->reconciliation_status);
        $this->assertSame($payment->id, $reconciliation->allocations->sole()->reconcilable_id);
    }

    public function test_outflow_links_existing_draft_expense_without_creating_a_duplicate(): void
    {
        $expense = $this->draftExpense();
        $transaction = $this->transaction(-50, 'EXISTING-EXPENSE-50', 'Existing Supplier Ltd');

        $reconciliation = app(LinkExistingExpenseToBankTransactionAction::class)->execute(
            $transaction,
            $expense,
            $this->user,
            'Matched to the supplier payment on the imported statement.',
        );

        $expense->refresh();
        $payment = ExpensePayment::withoutGlobalScopes()->sole();
        $bankLine = $payment->journalEntries()->firstOrFail()->lines()
            ->where('ledger_account_id', $this->bankLedger->id)
            ->firstOrFail();
        $allocation = $reconciliation->allocations->sole();

        $this->assertDatabaseCount('expenses', 1);
        $this->assertSame(Expense::STATUS_PARTIALLY_PAID, $expense->status);
        $this->assertSame('50.00', $expense->amount_paid);
        $this->assertSame('70.00', $expense->amount_due);
        $this->assertSame($this->user->id, $expense->approved_by);
        $this->assertSame($this->user->id, $expense->posted_by);
        $this->assertSame($expense->id, $payment->expense_id);
        $this->assertSame($this->bankAccount->id, $payment->bank_account_id);
        $this->assertSame('50.00', $payment->amount);
        $this->assertSame('50.00', $bankLine->credit_gbp);
        $this->assertSame(BankTransaction::STATUS_RECONCILED, $transaction->fresh()->reconciliation_status);
        $this->assertSame($payment->id, $allocation->reconcilable_id);
        $this->assertSame('supplier_payment', $allocation->event_type);
        $this->assertSame('50.00', $allocation->amount);
    }

    public function test_existing_expense_link_requires_post_and_draft_approval_permissions(): void
    {
        $expense = $this->draftExpense();
        $transaction = $this->transaction(-120, 'EXISTING-PERMISSION', 'Existing Supplier Ltd');
        $this->user->revokePermissionTo('post_finance');

        try {
            app(LinkExistingExpenseToBankTransactionAction::class)->execute($transaction, $expense, $this->user);
            $this->fail('Posting permission is required to link an existing expense.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot link and post', $exception->getMessage());
        }

        $this->user->givePermissionTo('post_finance');
        $this->user->revokePermissionTo('approve_finance');

        try {
            app(LinkExistingExpenseToBankTransactionAction::class)->execute($transaction, $expense, $this->user);
            $this->fail('Approval permission is required for a draft expense.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot approve', $exception->getMessage());
        }

        $this->assertSame(Expense::STATUS_DRAFT, $expense->fresh()->status);
        $this->assertSame(BankTransaction::STATUS_UNMATCHED, $transaction->fresh()->reconciliation_status);
        $this->assertDatabaseCount('expense_payments', 0);
        $this->assertDatabaseCount('bank_reconciliations', 0);
    }

    public function test_existing_expense_link_rolls_back_draft_transition_when_period_is_closed(): void
    {
        $expense = $this->draftExpense();
        $transaction = $this->transaction(-120, 'EXISTING-CLOSED-PERIOD', 'Existing Supplier Ltd');
        $this->period->update(['status' => AccountingPeriod::STATUS_CLOSED]);

        try {
            app(LinkExistingExpenseToBankTransactionAction::class)->execute($transaction, $expense, $this->user);
            $this->fail('A closed period must roll back the existing expense link workflow.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('is closed', $exception->getMessage());
        }

        $this->assertSame(Expense::STATUS_DRAFT, $expense->fresh()->status);
        $this->assertNull($expense->approved_at);
        $this->assertNull($expense->posted_at);
        $this->assertSame(BankTransaction::STATUS_UNMATCHED, $transaction->fresh()->reconciliation_status);
        $this->assertDatabaseCount('expense_payments', 0);
        $this->assertDatabaseCount('bank_reconciliations', 0);
    }

    public function test_existing_expense_link_rejects_foreign_tenant_and_currency(): void
    {
        $transaction = $this->transaction(-120, 'EXISTING-BOUNDARY', 'Existing Supplier Ltd');
        $otherBusiness = Business::create(['name' => 'Foreign Expense Ltd', 'slug' => 'foreign-expense', 'is_active' => true]);
        $foreignExpense = Expense::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'created_by' => $this->user->id,
            'status' => Expense::STATUS_POSTED,
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'subtotal' => 120,
            'vat_amount' => 0,
            'total' => 120,
            'amount_paid' => 0,
            'amount_due' => 120,
            'business_purpose' => 'Foreign tenant fixture',
        ]);

        try {
            app(LinkExistingExpenseToBankTransactionAction::class)->execute($transaction, $foreignExpense, $this->user);
            $this->fail('A foreign tenant expense must not be linked.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('does not belong', $exception->getMessage());
        }

        $eurExpense = $this->draftExpense([
            'currency' => 'EUR',
            'gbp_exchange_rate' => '0.85000000',
            'exchange_rate_source' => 'manual',
            'exchange_rate_date' => '2026-11-01',
        ]);

        try {
            app(LinkExistingExpenseToBankTransactionAction::class)->execute($transaction, $eurExpense, $this->user);
            $this->fail('An expense in another currency must not be linked.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('same currency', $exception->getMessage());
        }

        $this->assertDatabaseCount('expense_payments', 0);
        $this->assertSame(BankTransaction::STATUS_UNMATCHED, $transaction->fresh()->reconciliation_status);
    }

    public function test_gbp_receipt_can_settle_foreign_invoice_with_derived_fx_snapshot(): void
    {
        $invoice = $this->issuedInvoice([
            'currency' => 'EUR',
            'gbp_exchange_rate' => '0.80000000',
            'exchange_rate_source' => 'invoice_rate',
            'exchange_rate_date' => '2026-11-01',
        ]);
        $transaction = $this->transaction(102, 'EUR-CUSTOMER', 'Euro Customer');

        $reconciliation = $this->createRecord($transaction, [
            'invoice_id' => $invoice->id,
            'source_amount' => '120.00',
        ]);

        $payment = Payment::withoutGlobalScopes()->sole();
        $allocation = $reconciliation->allocations->sole();

        $this->assertSame('120.00', $payment->amount);
        $this->assertSame('102.00', $payment->amount_gbp);
        $this->assertSame('0.85000000', $payment->gbp_exchange_rate);
        $this->assertSame('102.00', $allocation->amount);
        $this->assertSame('120.00', $allocation->source_amount);
        $this->assertSame(BankTransaction::STATUS_RECONCILED, $transaction->fresh()->reconciliation_status);
    }

    public function test_closed_period_rolls_back_created_expense_payment_and_reconciliation(): void
    {
        $transaction = $this->transaction(-120, 'CLOSED-PERIOD', 'Blocked Supplier');
        $this->period->update(['status' => AccountingPeriod::STATUS_CLOSED]);

        try {
            $this->createRecord($transaction, [
                'source_amount' => '120.00',
                'tax_code_id' => $this->taxCode('S20')->id,
                'ledger_account_id' => $this->expenseLedger()->id,
                'category' => 'software',
                'description' => 'Blocked software expense',
                'business_purpose' => 'Rollback test',
            ]);
            $this->fail('A closed period must roll back the whole bank creation workflow.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('is closed', $exception->getMessage());
        }

        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('expense_payments', 0);
        $this->assertDatabaseCount('bank_reconciliations', 0);
        $this->assertSame(BankTransaction::STATUS_UNMATCHED, $transaction->fresh()->reconciliation_status);
    }

    public function test_action_requires_post_permission_and_rejects_cross_tenant_invoice(): void
    {
        $invoice = $this->issuedInvoice();
        $transaction = $this->transaction(120, 'SECURE-CREATION', 'Customer Ltd');
        $this->user->revokePermissionTo('post_finance');

        try {
            $this->createRecord($transaction, ['invoice_id' => $invoice->id]);
            $this->fail('Posting permission is required.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot create and post', $exception->getMessage());
        }

        $this->assertDatabaseCount('payments', 0);
        $this->user->givePermissionTo('post_finance');

        $otherBusiness = Business::create(['name' => 'Other Tenant Ltd', 'slug' => 'other-bank-creation', 'is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);
        $this->addMember($otherBusiness, $otherUser);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness, $otherUser);
        $otherClient = Client::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'company_name' => 'Other Customer',
            'status' => 'active',
        ]);
        $otherInvoice = Invoice::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'number' => 'INV-OTHER-001',
            'client_id' => $otherClient->id,
            'created_by' => $otherUser->id,
            'status' => 'sent',
            'currency' => 'GBP',
            'total' => 120,
            'amount_due' => 120,
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'due_date' => '2026-12-01',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('does not belong');
        $this->createRecord($transaction, ['invoice_id' => $otherInvoice->id]);
    }

    public function test_filament_view_exposes_direction_specific_creation_action(): void
    {
        $inflow = $this->transaction(120, 'UI-INFLOW', 'UI Customer');
        $outflow = $this->transaction(-120, 'UI-OUTFLOW', 'UI Supplier');
        $this->actingAs($this->user);

        $this->get(BankTransactionResource::getUrl('view', ['record' => $inflow]))
            ->assertOk()
            ->assertSee(__('finance.bank_reconciliation.actions.create_customer_payment'))
            ->assertDontSee(__('finance.bank_reconciliation.actions.create_supplier_expense'));
        $this->get(BankTransactionResource::getUrl('view', ['record' => $outflow]))
            ->assertOk()
            ->assertSee(__('finance.bank_reconciliation.actions.create_supplier_expense'))
            ->assertSee(__('finance.bank_reconciliation.actions.link_existing_expense'))
            ->assertDontSee(__('finance.bank_reconciliation.actions.create_customer_payment'));
    }

    /** @param array<string, mixed> $data */
    private function createRecord(BankTransaction $transaction, array $data): BankReconciliation
    {
        return app(CreateRecordFromBankTransactionAction::class)->execute($transaction, $this->user, $data);
    }

    /** @param array<string, mixed> $overrides */
    private function issuedInvoice(array $overrides = []): Invoice
    {
        $client = Client::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'company_name' => 'Bank Customer Ltd',
            'status' => 'active',
        ]);
        $invoice = Invoice::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => 'INV-BANK-'.fake()->unique()->numerify('######'),
            'client_id' => $client->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'due_date' => '2026-12-01',
            ...$overrides,
        ]);
        InvoiceItem::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'invoice_id' => $invoice->id,
            'tax_code_id' => $this->taxCode('S20')->id,
            'description' => 'Bank creation service',
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        return app(IssueInvoiceAction::class)->execute($invoice, $this->user);
    }

    /** @param array<string, mixed> $overrides */
    private function draftExpense(array $overrides = []): Expense
    {
        $supplier = Supplier::factory()->for($this->business)->create();

        return app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_id' => $supplier->id,
            'supplier_invoice_number' => 'EXISTING-'.fake()->unique()->numerify('######'),
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'due_date' => '2026-11-30',
            'received_date' => '2026-11-01',
            'payment_method' => 'bank_transfer',
            'business_purpose' => 'Existing software cost used for client work',
            'items' => [[
                'description' => 'Existing software expense',
                'category' => 'software',
                'quantity' => 1,
                'unit_price' => 100,
                'tax_code_id' => $this->taxCode('S20')->id,
                'ledger_account_id' => $this->expenseLedger()->id,
                'supply_date' => '2026-11-01',
            ]],
            ...$overrides,
        ]);
    }

    private function transaction(float $amount, string $reference, string $counterparty): BankTransaction
    {
        return BankTransaction::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'bank_account_id' => $this->bankAccount->id,
            'booking_date' => '2026-11-05',
            'amount' => $amount,
            'currency' => 'GBP',
            'reference' => $reference,
            'counterparty' => $counterparty,
            'source_hash' => hash('sha256', $reference.'|'.$amount),
            'reconciliation_status' => BankTransaction::STATUS_UNMATCHED,
        ]);
    }

    private function taxCode(string $code): TaxCode
    {
        return TaxCode::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function expenseLedger(): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->activePostable()
            ->where('type', 'expense')
            ->firstOrFail();
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
