<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateBankAccountAction;
use App\Actions\Finance\FindBankTransactionMatchesAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\ReconcileBankTransactionAction;
use App\Data\Finance\BankTransactionMatchCandidate;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\Invoice;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseCreditRefund;
use App\Models\Supplier;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BankTransactionMatchingTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private BankAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Matcher Ltd', 'slug' => 'matcher', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);

        foreach (['view_finance', 'manage_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo(['view_finance', 'manage_finance']);
        $cashAccount = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', '1100')
            ->firstOrFail();
        $this->account = app(CreateBankAccountAction::class)->execute($this->business, $this->user, [
            'name' => 'Operating account',
            'bank_name' => 'Example Bank',
            'account_number' => '12345678',
            'sort_code' => '12-34-56',
            'currency' => 'GBP',
            'ledger_account_id' => $cashAccount->id,
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
    }

    public function test_exact_customer_payment_scores_all_four_rules(): void
    {
        $payment = $this->customerPayment([
            'amount' => 125,
            'amount_gbp' => 125,
            'reference' => 'RCPT-125',
            'paid_at' => '2026-11-05 10:00:00',
        ], ['company_name' => 'Acme Limited']);
        $transaction = $this->transaction(125, [
            'reference' => 'RCPT-125',
            'counterparty' => 'Acme Limited',
            'booking_date' => '2026-11-05',
        ]);

        $candidates = $this->find($transaction);

        $this->assertCount(1, $candidates);
        $candidate = $candidates[0];
        $this->assertSame('payment:'.$payment->id.':receipt', $candidate->candidateKey);
        $this->assertSame(Payment::class, $candidate->sourceType);
        $this->assertSame('customer_payment', $candidate->eventType);
        $this->assertSame('inflow', $candidate->direction);
        $this->assertSame('125.00', $candidate->comparableAmount);
        $this->assertSame('0.00', $candidate->amountDifference);
        $this->assertSame(0, $candidate->dateDifferenceDays);
        $this->assertSame(100, $candidate->score);
        $this->assertSame('exact', $candidate->confidence);
        $this->assertSame(['amount' => 50, 'date' => 20, 'reference' => 20, 'counterparty' => 10], $candidate->scoreBreakdown);
        $this->assertSame(['amount_exact', 'date_same_day', 'reference_exact', 'counterparty_exact'], $candidate->reasons);
    }

    public function test_direction_selects_supplier_payment_and_customer_refund_for_outflows(): void
    {
        $supplierPayment = $this->supplierPayment([
            'amount' => 80,
            'amount_gbp' => 80,
            'reference' => 'SUP-80',
            'paid_at' => '2026-11-05 09:00:00',
        ], ['name' => 'Hosting Vendor']);
        $refundedPayment = $this->customerPayment([
            'amount' => 80,
            'amount_gbp' => 80,
            'reference' => 'CUSTOMER-REFUND',
            'status' => 'refunded',
            'paid_at' => '2026-10-30 09:00:00',
            'refunded_at' => '2026-11-05 12:00:00',
        ], ['company_name' => 'Refunded Client']);
        $transaction = $this->transaction(-80, [
            'reference' => 'CUSTOMER-REFUND',
            'counterparty' => 'Refunded Client',
        ]);

        $candidates = $this->find($transaction);

        $this->assertSame('payment:'.$refundedPayment->id.':refund', $candidates[0]->candidateKey);
        $this->assertSame('customer_refund', $candidates[0]->eventType);
        $this->assertSame('-80.00', $candidates[0]->comparableAmount);
        $this->assertSame('expense_payment:'.$supplierPayment->id, $candidates[1]->candidateKey);
        $this->assertSame('supplier_payment', $candidates[1]->eventType);
        $this->assertSame('outflow', $candidates[1]->direction);
        $this->assertNotContains('payment:'.$refundedPayment->id.':receipt', array_column($candidates, 'candidateKey'));
    }

    public function test_owner_funded_supplier_payment_is_not_a_bank_match_candidate(): void
    {
        $ownerPayment = $this->supplierPayment([
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'bank_account_id' => null,
            'amount' => 80,
            'amount_gbp' => 80,
            'reference' => 'OWNER-80',
        ]);
        $transaction = $this->transaction(-80, ['reference' => 'OWNER-80']);

        $candidateKeys = array_column($this->find($transaction), 'candidateKey');

        $this->assertNotContains('expense_payment:'.$ownerPayment->id, $candidateKeys);
    }

    public function test_owner_funded_supplier_payment_cannot_be_forced_into_bank_reconciliation(): void
    {
        $ownerPayment = $this->supplierPayment([
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'bank_account_id' => null,
            'amount' => 80,
            'amount_gbp' => 80,
            'reference' => 'OWNER-FORCED-80',
        ]);
        $transaction = $this->transaction(-80, ['reference' => 'OWNER-FORCED-80']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only a supplier payment from this business bank account can be reconciled.');

        app(ReconcileBankTransactionAction::class)->execute($transaction, $this->user, [[
            'candidate_key' => 'expense_payment:'.$ownerPayment->id,
            'amount' => 80,
        ]]);
    }

    public function test_supplier_refund_is_an_inflow_candidate(): void
    {
        [$expense, $supplier] = $this->expense(['supplier_invoice_number' => 'BILL-42'], ['name' => 'Office Supplier']);
        $creditNote = PurchaseCreditNote::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => 'PCN-42',
            'expense_id' => $expense->id,
            'supplier_id' => $supplier->id,
            'created_by' => $this->user->id,
            'status' => PurchaseCreditNote::STATUS_ISSUED,
            'currency' => 'GBP',
            'reason' => 'Overpayment',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'total' => 45,
        ]);
        $refund = PurchaseCreditRefund::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'purchase_credit_note_id' => $creditNote->id,
            'expense_id' => $expense->id,
            'received_by' => $this->user->id,
            'amount' => 45,
            'amount_gbp' => 45,
            'currency' => 'GBP',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-11-05',
            'method' => 'bank_transfer',
            'reference' => 'REFUND-42',
            'received_at' => '2026-11-05',
        ]);
        $transaction = $this->transaction(45, [
            'reference' => 'REFUND-42',
            'counterparty' => 'Office Supplier',
        ]);

        $candidate = $this->find($transaction)[0];

        $this->assertSame('purchase_credit_refund:'.$refund->id, $candidate->candidateKey);
        $this->assertSame(PurchaseCreditRefund::class, $candidate->sourceType);
        $this->assertSame('supplier_refund', $candidate->eventType);
        $this->assertSame(100, $candidate->score);
    }

    public function test_gbp_transaction_uses_immutable_gbp_snapshot_for_foreign_payment(): void
    {
        $payment = $this->customerPayment([
            'amount' => 100,
            'amount_gbp' => 84.50,
            'currency' => 'EUR',
            'reference' => 'EUR-100',
            'gbp_exchange_rate' => 0.845,
            'exchange_rate_source' => 'manual',
            'exchange_rate_date' => '2026-11-05',
        ], ['company_name' => 'European Client']);
        $transaction = $this->transaction(84.50, [
            'reference' => 'EUR-100',
            'counterparty' => 'European Client',
        ]);

        $candidate = $this->find($transaction)[0];

        $this->assertSame($payment->id, $candidate->sourceId);
        $this->assertSame('84.50', $candidate->comparableAmount);
        $this->assertSame('GBP', $candidate->comparableCurrency);
        $this->assertSame('100.00', $candidate->originalAmount);
        $this->assertSame('EUR', $candidate->originalCurrency);
        $this->assertSame(50, $candidate->scoreBreakdown['amount']);
    }

    public function test_same_day_partial_amount_remains_available_without_text_match(): void
    {
        $payment = $this->customerPayment([
            'amount' => 300,
            'amount_gbp' => 300,
            'reference' => 'DOCUMENT-300',
        ], ['company_name' => 'Partial Client']);
        $transaction = $this->transaction(200, [
            'reference' => 'UNRELATED-BANK-TEXT',
            'counterparty' => null,
        ]);

        $candidate = $this->find($transaction)[0];

        $this->assertSame($payment->id, $candidate->sourceId);
        $this->assertSame(45, $candidate->score);
        $this->assertSame('low', $candidate->confidence);
        $this->assertSame(25, $candidate->scoreBreakdown['amount']);
        $this->assertSame(20, $candidate->scoreBreakdown['date']);
        $this->assertSame(0, $candidate->scoreBreakdown['reference']);
    }

    public function test_scoring_is_deterministic_and_filters_weak_or_foreign_candidates(): void
    {
        $exact = $this->customerPayment([
            'amount' => 200,
            'amount_gbp' => 200,
            'reference' => 'MATCH-200',
            'paid_at' => '2026-11-06 09:00:00',
        ], ['company_name' => 'Best Client']);
        $partial = $this->customerPayment([
            'amount' => 300,
            'amount_gbp' => 300,
            'reference' => 'MATCH-200 PART PAYMENT',
            'paid_at' => '2026-11-08 09:00:00',
        ], ['company_name' => 'Best Client']);
        $this->customerPayment([
            'amount' => 1000,
            'amount_gbp' => 1000,
            'reference' => 'UNRELATED',
            'paid_at' => '2026-11-05 09:00:00',
        ], ['company_name' => 'Unknown']);
        $foreignBusiness = Business::create(['name' => 'Foreign', 'slug' => 'foreign-matcher', 'is_active' => true]);
        $this->customerPaymentForBusiness($foreignBusiness, [
            'amount' => 200,
            'amount_gbp' => 200,
            'reference' => 'MATCH-200',
        ]);
        $transaction = $this->transaction(200, [
            'reference' => 'MATCH-200',
            'counterparty' => 'Best Client',
        ]);

        $candidates = $this->find($transaction);

        $this->assertCount(2, $candidates);
        $this->assertSame($exact->id, $candidates[0]->sourceId);
        $this->assertSame(96, $candidates[0]->score);
        $this->assertSame($partial->id, $candidates[1]->sourceId);
        $this->assertSame(67, $candidates[1]->score);
        $this->assertSame('medium', $candidates[1]->confidence);
        $this->assertSame(25, $candidates[1]->scoreBreakdown['amount']);
    }

    public function test_action_requires_membership_permission_and_unmatched_status(): void
    {
        $transaction = $this->transaction(25);
        $outsider = User::factory()->create(['is_active' => true]);
        $outsider->givePermissionTo('view_finance');

        try {
            app(FindBankTransactionMatchesAction::class)->execute($transaction, $outsider);
            $this->fail('An actor from another tenant must be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('does not belong', $exception->getMessage());
        }

        $this->user->revokePermissionTo('view_finance');

        try {
            $this->find($transaction);
            $this->fail('Finance view permission must be required.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot view', $exception->getMessage());
        }

        $this->user->givePermissionTo('view_finance');
        $transaction->update(['reconciliation_status' => 'matched']);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only unmatched');
        $this->find($transaction);
    }

    /** @return list<BankTransactionMatchCandidate> */
    private function find(BankTransaction $transaction): array
    {
        return app(FindBankTransactionMatchesAction::class)->execute($transaction, $this->user);
    }

    /** @param array<string, mixed> $overrides */
    private function transaction(float $amount, array $overrides = []): BankTransaction
    {
        return BankTransaction::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'bank_account_id' => $this->account->id,
            'booking_date' => '2026-11-05',
            'amount' => $amount,
            'currency' => 'GBP',
            'reference' => 'BANK-REF',
            'counterparty' => null,
            'source_hash' => hash('sha256', json_encode([$amount, $overrides, fake()->uuid()], JSON_THROW_ON_ERROR)),
            'reconciliation_status' => BankTransaction::STATUS_UNMATCHED,
            ...$overrides,
        ]);
    }

    /** @param array<string, mixed> $paymentOverrides @param array<string, mixed> $clientOverrides */
    private function customerPayment(array $paymentOverrides = [], array $clientOverrides = []): Payment
    {
        return $this->customerPaymentForBusiness($this->business, $paymentOverrides, $clientOverrides);
    }

    /** @param array<string, mixed> $paymentOverrides @param array<string, mixed> $clientOverrides */
    private function customerPaymentForBusiness(Business $business, array $paymentOverrides = [], array $clientOverrides = []): Payment
    {
        $client = Client::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'company_name' => 'Customer Ltd',
            'status' => 'active',
            ...$clientOverrides,
        ]);
        $invoice = Invoice::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'client_id' => $client->id,
            'created_by' => $this->user->id,
            'status' => 'paid',
            'currency' => $paymentOverrides['currency'] ?? 'GBP',
            'total' => $paymentOverrides['amount'] ?? 100,
            'amount_paid' => $paymentOverrides['amount'] ?? 100,
            'amount_due' => 0,
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'due_date' => '2026-11-30',
        ]);

        return Payment::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'invoice_id' => $invoice->id,
            'created_by' => $this->user->id,
            'amount' => 100,
            'amount_gbp' => 100,
            'currency' => 'GBP',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-11-05',
            'method' => 'bank_transfer',
            'status' => 'completed',
            'reference' => 'PAY-'.fake()->unique()->numerify('######'),
            'paid_at' => '2026-11-05 09:00:00',
            ...$paymentOverrides,
        ]);
    }

    /** @param array<string, mixed> $paymentOverrides @param array<string, mixed> $supplierOverrides */
    private function supplierPayment(array $paymentOverrides = [], array $supplierOverrides = []): ExpensePayment
    {
        [$expense] = $this->expense([], $supplierOverrides);

        return ExpensePayment::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'expense_id' => $expense->id,
            'bank_account_id' => $this->account->id,
            'settlement_source' => ExpensePayment::SOURCE_BANK_ACCOUNT,
            'created_by' => $this->user->id,
            'amount' => 100,
            'amount_gbp' => 100,
            'currency' => 'GBP',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-11-05',
            'method' => 'bank_transfer',
            'reference' => 'SUP-'.fake()->unique()->numerify('######'),
            'paid_at' => '2026-11-05 09:00:00',
            ...$paymentOverrides,
        ]);
    }

    /** @param array<string, mixed> $expenseOverrides @param array<string, mixed> $supplierOverrides @return array{Expense, Supplier} */
    private function expense(array $expenseOverrides = [], array $supplierOverrides = []): array
    {
        $supplier = Supplier::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Supplier Ltd',
            ...$supplierOverrides,
        ]);
        $expense = Expense::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'supplier_id' => $supplier->id,
            'created_by' => $this->user->id,
            'supplier_invoice_number' => 'BILL-'.fake()->unique()->numerify('######'),
            'status' => Expense::STATUS_PAID,
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'total' => 100,
            'total_gbp' => 100,
            'amount_paid' => 100,
            'amount_due' => 0,
            ...$expenseOverrides,
        ]);

        return [$expense, $supplier];
    }
}
