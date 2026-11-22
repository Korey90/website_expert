<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\ApproveExpenseAction;
use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\CreatePurchaseCreditNoteAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\IssueInvoiceAction;
use App\Actions\Finance\IssuePurchaseCreditNoteAction;
use App\Actions\Finance\PostExpenseAction;
use App\Actions\Finance\RecordExpensePaymentAction;
use App\Actions\Finance\RecordInvoicePaymentAction;
use App\Actions\Finance\RecordPurchaseCreditRefundAction;
use App\Actions\Finance\RefundInvoicePaymentAction;
use App\Actions\Finance\UpdateAccountingProfileAction;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use App\Services\Finance\AgedReceivablesPayablesService;
use App\Services\Finance\BalanceSheetService;
use App\Services\Finance\ManagementCashFlowService;
use App\Services\Finance\PaymentJournalPayloadFactory;
use App\Services\Finance\ProfitAndLossService;
use App\Services\Finance\TrialBalanceService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentPostingTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Client $client;

    private Supplier $supplier;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Settlement Ledger Ltd', 'slug' => 'settlement-ledger', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $this->client = Client::create([
            'business_id' => $this->business->id,
            'company_name' => 'Settlement Customer Ltd',
            'status' => 'active',
        ]);
        $this->supplier = Supplier::factory()->for($this->business)->create();
        Permission::findOrCreate('post_finance', 'web');
        Permission::findOrCreate('manage_finance', 'web');
        $this->user->givePermissionTo(['post_finance', 'manage_finance']);
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
    }

    public function test_customer_receipt_posts_bank_against_trade_debtors_and_is_idempotent(): void
    {
        $invoice = $this->issuedInvoice();
        $payment = app(RecordInvoicePaymentAction::class)->execute($invoice, $this->user, [
            'amount' => 40,
            'method' => 'bank_transfer',
            'reference' => 'RCPT-001',
            'paid_at' => '2026-11-05',
        ]);
        $journal = $payment->journalEntries()->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame('40.00', $journal->lines->firstWhere('ledgerAccount.code', '1100')->debit_gbp);
        $this->assertSame('40.00', $journal->lines->firstWhere('ledgerAccount.code', '1200')->credit_gbp);
        $this->assertSame('partially_paid', $payment->invoice->status);
        $this->assertSame('40.00', $payment->invoice->amount_paid);
        $this->assertSame('80.00', $payment->invoice->amount_due);

        $retry = app(RecordInvoicePaymentAction::class)->execute($invoice, $this->user, [
            'amount' => 40,
            'method' => 'bank_transfer',
            'reference' => 'RCPT-001',
        ]);
        $this->assertTrue($retry->is($payment));
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('journal_entries', 2);

        $this->expectException(DomainException::class);
        $payment->delete();
    }

    public function test_supplier_payment_posts_trade_creditors_against_bank_and_updates_expense(): void
    {
        $expense = $this->postedExpense();
        $payment = app(RecordExpensePaymentAction::class)->execute($expense, $this->user, [
            'amount' => 40,
            'method' => 'bank_transfer',
            'reference' => 'SUP-001',
            'paid_at' => '2026-11-05',
        ]);
        $journal = $payment->journalEntries()->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame('40.00', $journal->lines->firstWhere('ledgerAccount.code', '2100')->debit_gbp);
        $this->assertSame('40.00', $journal->lines->firstWhere('ledgerAccount.code', '1100')->credit_gbp);
        $this->assertSame(Expense::STATUS_PARTIALLY_PAID, $payment->expense->status);
        $this->assertSame('40.00', $payment->expense->amount_paid);
        $this->assertSame('80.00', $payment->expense->amount_due);
        $this->assertSame($this->supplier->id, $journal->lines->first()->supplier_id);
    }

    public function test_sole_trader_owner_funded_payment_posts_capital_without_moving_business_cash(): void
    {
        $this->business->accountingProfile()->withoutGlobalScopes()->update(['entity_type' => 'sole_trader']);
        $expense = $this->postedExpense();

        $payment = app(RecordExpensePaymentAction::class)->execute($expense, $this->user, [
            'amount' => 40,
            'method' => 'card',
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'reference' => 'OWNER-FUNDS-001',
            'paid_at' => '2026-11-05',
        ]);
        $journal = $payment->journalEntries()->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame('40.00', $journal->lines->firstWhere('ledgerAccount.code', '2100')->debit_gbp);
        $this->assertSame('40.00', $journal->lines->firstWhere('ledgerAccount.code', '3300')->credit_gbp);
        $this->assertNull($journal->lines->firstWhere('ledgerAccount.code', '1100'));
        $this->assertNull($payment->bank_account_id);
        $payload = app(PaymentJournalPayloadFactory::class)->forExpensePayment($payment);
        $this->assertSame('owner_funds', $payload['source_snapshot']['settlement_source']);
        $this->assertSame('3300', $payload['source_snapshot']['settlement_ledger_account_code']);

        $profitAndLoss = app(ProfitAndLossService::class)->report($this->business->id, $this->period->id);
        $balanceSheet = app(BalanceSheetService::class)->report($this->business->id, $this->period->id);
        $trialBalance = app(TrialBalanceService::class)->report($this->business->id, $this->period->id);
        $cashFlow = app(ManagementCashFlowService::class)->report($this->business->id, $this->period->id);
        $ageing = app(AgedReceivablesPayablesService::class)->report($this->business->id, '2026-11-05');
        $balanceRows = $balanceSheet['sections']->flatMap(fn (object $section) => $section->rows)->keyBy('code');

        $this->assertSame(100.0, $profitAndLoss['totals']->operating_expenses_gbp);
        $this->assertSame(40.0, $balanceRows['3300']->amount_gbp);
        $this->assertTrue($balanceSheet['totals']->is_balanced);
        $this->assertTrue($trialBalance['totals']->is_balanced);
        $this->assertSame(0.0, $cashFlow['totals']->cash_inflows_gbp);
        $this->assertSame(0.0, $cashFlow['totals']->cash_outflows_gbp);
        $this->assertSame(80.0, $ageing['sections']->firstWhere('key', 'payables')->totals->total_gbp);
    }

    public function test_owner_funded_payment_is_rejected_for_a_limited_company(): void
    {
        $this->business->accountingProfile()->withoutGlobalScopes()->update(['entity_type' => 'limited_company']);
        $expense = $this->postedExpense();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('available only to sole traders');

        app(RecordExpensePaymentAction::class)->execute($expense, $this->user, [
            'amount' => 40,
            'method' => 'card',
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'reference' => 'OWNER-FUNDS-INVALID',
            'paid_at' => '2026-11-05',
        ]);
    }

    public function test_owner_funded_payment_requires_post_finance_permission(): void
    {
        $this->business->accountingProfile()->withoutGlobalScopes()->update(['entity_type' => 'sole_trader']);
        $expense = $this->postedExpense();
        $this->user->revokePermissionTo('post_finance');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('cannot post supplier payments');

        app(RecordExpensePaymentAction::class)->execute($expense, $this->user, [
            'amount' => 40,
            'method' => 'card',
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'reference' => 'OWNER-FUNDS-FORBIDDEN',
            'paid_at' => '2026-11-05',
        ]);
    }

    public function test_entity_type_cannot_change_after_an_owner_funded_payment_is_posted(): void
    {
        $profile = $this->business->accountingProfile()->withoutGlobalScopes()->firstOrFail();
        $profile->update(['entity_type' => 'sole_trader']);
        $expense = $this->postedExpense();
        app(RecordExpensePaymentAction::class)->execute($expense, $this->user, [
            'amount' => 40,
            'method' => 'card',
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'reference' => 'OWNER-FUNDS-LOCK',
            'paid_at' => '2026-11-05',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('entity type cannot be changed');

        app(UpdateAccountingProfileAction::class)->execute($profile, $this->user, [
            'entity_type' => 'limited_company',
        ]);
    }

    public function test_owner_funded_payments_are_idempotent_and_can_settle_an_expense_in_parts(): void
    {
        $this->business->accountingProfile()->withoutGlobalScopes()->update(['entity_type' => 'sole_trader']);
        $expense = $this->postedExpense();
        $data = [
            'amount' => 40,
            'method' => 'card',
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'reference' => 'OWNER-PART-1',
            'paid_at' => '2026-11-05',
        ];

        $first = app(RecordExpensePaymentAction::class)->execute($expense, $this->user, $data);
        $retry = app(RecordExpensePaymentAction::class)->execute($expense, $this->user, $data);
        $this->assertTrue($retry->is($first));
        $this->assertDatabaseCount('expense_payments', 1);

        try {
            app(RecordExpensePaymentAction::class)->execute($expense, $this->user, [
                ...$data,
                'settlement_source' => ExpensePayment::SOURCE_CASH_ACCOUNT,
            ]);
            $this->fail('Changing settlement source for the same reference should fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('different payment data', $exception->getMessage());
        }

        $second = app(RecordExpensePaymentAction::class)->execute($expense->fresh(), $this->user, [
            'amount' => 80,
            'method' => 'card',
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'reference' => 'OWNER-PART-2',
            'paid_at' => '2026-11-06',
        ]);

        $this->assertSame(Expense::STATUS_PAID, $second->expense->status);
        $this->assertSame('120.00', $second->expense->amount_paid);
        $this->assertSame('0.00', $second->expense->amount_due);
    }

    public function test_missing_owner_capital_account_rolls_back_the_payment_and_expense_status(): void
    {
        $this->business->accountingProfile()->withoutGlobalScopes()->update(['entity_type' => 'sole_trader']);
        $expense = $this->postedExpense();
        $this->business->ledgerAccounts()->withoutGlobalScopes()->where('code', '3300')->delete();

        try {
            app(RecordExpensePaymentAction::class)->execute($expense, $this->user, [
                'amount' => 40,
                'method' => 'card',
                'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
                'reference' => 'OWNER-NO-ACCOUNT',
                'paid_at' => '2026-11-05',
            ]);
            $this->fail('An owner-funded payment without account 3300 should fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('3300', $exception->getMessage());
        }

        $this->assertDatabaseCount('expense_payments', 0);
        $this->assertSame(Expense::STATUS_POSTED, $expense->fresh()->status);
        $this->assertSame('120.00', $expense->fresh()->amount_due);
    }

    public function test_customer_refund_reverses_receipt_and_reopens_invoice_balance(): void
    {
        $invoice = $this->issuedInvoice();
        $payment = app(RecordInvoicePaymentAction::class)->execute($invoice, $this->user, [
            'amount' => 120,
            'method' => 'bank_transfer',
            'reference' => 'RCPT-FULL',
            'paid_at' => '2026-11-05',
        ]);

        $refunded = app(RefundInvoicePaymentAction::class)->execute($payment, $this->user);
        $refundJournal = $refunded->journalEntries()
            ->where('idempotency_key', "invoice_payment:{$payment->id}:refund")
            ->with('lines.ledgerAccount')
            ->firstOrFail();

        $this->assertSame('refunded', $refunded->status);
        $this->assertSame('120.00', $refundJournal->lines->firstWhere('ledgerAccount.code', '1200')->debit_gbp);
        $this->assertSame('120.00', $refundJournal->lines->firstWhere('ledgerAccount.code', '1100')->credit_gbp);
        $this->assertSame('sent', $refunded->invoice->status);
        $this->assertSame('0.00', $refunded->invoice->amount_paid);
        $this->assertSame('120.00', $refunded->invoice->amount_due);

        app(RefundInvoicePaymentAction::class)->execute($refunded, $this->user);
        $this->assertDatabaseCount('journal_entries', 3);
    }

    public function test_supplier_cash_refund_posts_bank_against_trade_creditors(): void
    {
        $expense = $this->postedExpense();
        app(RecordExpensePaymentAction::class)->execute($expense, $this->user, [
            'amount' => 120,
            'method' => 'bank_transfer',
            'reference' => 'SUP-FULL',
            'paid_at' => '2026-11-05',
        ]);
        $expense->refresh();
        $credit = app(CreatePurchaseCreditNoteAction::class)->execute($expense, $this->user, [
            'reason' => 'Supplier cash refund',
            'issue_date' => '2026-11-05',
            'tax_point' => '2026-11-05',
            'items' => [[
                'expense_item_id' => $expense->items->first()->id,
                'quantity' => 0.5,
            ]],
        ]);
        $credit = app(IssuePurchaseCreditNoteAction::class)->execute($credit, $this->user);

        $refund = app(RecordPurchaseCreditRefundAction::class)->execute($credit, $this->user, [
            'amount' => 60,
            'method' => 'bank_transfer',
            'reference' => 'SUP-REF-001',
            'received_at' => '2026-11-05',
        ]);
        $journal = $refund->journalEntries()->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame('60.00', $journal->lines->firstWhere('ledgerAccount.code', '1100')->debit_gbp);
        $this->assertSame('60.00', $journal->lines->firstWhere('ledgerAccount.code', '2100')->credit_gbp);
        $this->assertSame('60.00', $refund->amount_gbp);
    }

    public function test_foreign_currency_payment_preserves_original_and_gbp_snapshots(): void
    {
        $invoice = $this->issuedInvoice([
            'currency' => 'EUR',
            'gbp_exchange_rate' => '0.85000000',
            'exchange_rate_source' => 'manual',
            'exchange_rate_date' => '2026-11-01',
        ]);
        $payment = app(RecordInvoicePaymentAction::class)->execute($invoice, $this->user, [
            'amount' => 120,
            'method' => 'bank_transfer',
            'reference' => 'EUR-001',
            'paid_at' => '2026-11-05',
        ]);
        $bankLine = $payment->journalEntries()->firstOrFail()->lines()->whereHas(
            'ledgerAccount',
            fn ($query) => $query->where('code', '1100'),
        )->firstOrFail();

        $this->assertSame('102.00', $payment->amount_gbp);
        $this->assertSame('102.00', $bankLine->debit_gbp);
        $this->assertSame('EUR', $bankLine->original_currency);
        $this->assertSame('120.00', $bankLine->original_amount);
        $this->assertSame('0.85000000', $bankLine->exchange_rate);
    }

    public function test_customer_receipt_posts_realised_fx_gain_and_refund_reverses_it(): void
    {
        $invoice = $this->issuedInvoice([
            'currency' => 'EUR',
            'gbp_exchange_rate' => '0.85000000',
            'exchange_rate_source' => 'invoice_rate',
            'exchange_rate_date' => '2026-11-01',
        ]);
        $payment = app(RecordInvoicePaymentAction::class)->execute($invoice, $this->user, [
            'amount' => 120,
            'method' => 'bank_transfer',
            'reference' => 'EUR-GAIN-001',
            'paid_at' => '2026-11-05',
            'gbp_exchange_rate' => '0.90000000',
            'exchange_rate_source' => 'bank_statement',
            'exchange_rate_date' => '2026-11-05',
        ]);
        $journal = $payment->journalEntries()->where('idempotency_key', "invoice_payment:{$payment->id}:receipt")
            ->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame('108.00', $payment->amount_gbp);
        $this->assertSame('102.00', $payment->carrying_amount_gbp);
        $this->assertSame('6.00', $payment->realised_fx_gbp);
        $this->assertSame('108.00', $journal->lines->firstWhere('ledgerAccount.code', '1100')->debit_gbp);
        $this->assertSame('102.00', $journal->lines->firstWhere('ledgerAccount.code', '1200')->credit_gbp);
        $this->assertSame('0.90000000', $journal->lines->firstWhere('ledgerAccount.code', '1100')->exchange_rate);
        $this->assertSame('0.85000000', $journal->lines->firstWhere('ledgerAccount.code', '1200')->exchange_rate);
        $this->assertSame('6.00', $journal->lines->firstWhere('ledgerAccount.code', '4300')->credit_gbp);

        $refunded = app(RefundInvoicePaymentAction::class)->execute($payment, $this->user);
        $reversal = $refunded->journalEntries()->where('idempotency_key', "invoice_payment:{$payment->id}:refund")
            ->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame('108.00', $reversal->lines->firstWhere('ledgerAccount.code', '1100')->credit_gbp);
        $this->assertSame('102.00', $reversal->lines->firstWhere('ledgerAccount.code', '1200')->debit_gbp);
        $this->assertSame('6.00', $reversal->lines->firstWhere('ledgerAccount.code', '4300')->debit_gbp);
    }

    public function test_customer_receipt_posts_realised_fx_loss(): void
    {
        $invoice = $this->issuedInvoice([
            'currency' => 'EUR',
            'gbp_exchange_rate' => '0.85000000',
            'exchange_rate_source' => 'invoice_rate',
            'exchange_rate_date' => '2026-11-01',
        ]);
        $payment = app(RecordInvoicePaymentAction::class)->execute($invoice, $this->user, [
            'amount' => 60,
            'method' => 'bank_transfer',
            'reference' => 'EUR-LOSS-001',
            'paid_at' => '2026-11-05',
            'gbp_exchange_rate' => '0.80000000',
            'exchange_rate_source' => 'bank_statement',
            'exchange_rate_date' => '2026-11-05',
        ]);
        $journal = $payment->journalEntries()->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame('48.00', $payment->amount_gbp);
        $this->assertSame('51.00', $payment->carrying_amount_gbp);
        $this->assertSame('-3.00', $payment->realised_fx_gbp);
        $this->assertSame('48.00', $journal->lines->firstWhere('ledgerAccount.code', '1100')->debit_gbp);
        $this->assertSame('51.00', $journal->lines->firstWhere('ledgerAccount.code', '1200')->credit_gbp);
        $this->assertSame('3.00', $journal->lines->firstWhere('ledgerAccount.code', '7100')->debit_gbp);
    }

    public function test_supplier_payment_posts_realised_fx_gain_and_loss(): void
    {
        $this->business->accountingProfile()->withoutGlobalScopes()->update(['entity_type' => 'sole_trader']);
        $gainExpense = $this->postedExpense([
            'currency' => 'EUR',
            'gbp_exchange_rate' => '0.85000000',
            'exchange_rate_source' => 'bill_rate',
            'exchange_rate_date' => '2026-11-01',
        ]);
        $gainPayment = app(RecordExpensePaymentAction::class)->execute($gainExpense, $this->user, [
            'amount' => 120,
            'method' => 'bank_transfer',
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'reference' => 'SUP-FX-GAIN',
            'paid_at' => '2026-11-05',
            'gbp_exchange_rate' => '0.80000000',
            'exchange_rate_source' => 'bank_statement',
            'exchange_rate_date' => '2026-11-05',
        ]);
        $gainJournal = $gainPayment->journalEntries()->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame('96.00', $gainPayment->amount_gbp);
        $this->assertSame('102.00', $gainPayment->carrying_amount_gbp);
        $this->assertSame('6.00', $gainPayment->realised_fx_gbp);
        $this->assertSame('102.00', $gainJournal->lines->firstWhere('ledgerAccount.code', '2100')->debit_gbp);
        $this->assertSame('96.00', $gainJournal->lines->firstWhere('ledgerAccount.code', '3300')->credit_gbp);
        $this->assertSame('0.85000000', $gainJournal->lines->firstWhere('ledgerAccount.code', '2100')->exchange_rate);
        $this->assertSame('0.80000000', $gainJournal->lines->firstWhere('ledgerAccount.code', '3300')->exchange_rate);
        $this->assertSame('6.00', $gainJournal->lines->firstWhere('ledgerAccount.code', '4300')->credit_gbp);

        $lossExpense = $this->postedExpense([
            'currency' => 'EUR',
            'gbp_exchange_rate' => '0.85000000',
            'exchange_rate_source' => 'bill_rate',
            'exchange_rate_date' => '2026-11-01',
        ]);
        $lossPayment = app(RecordExpensePaymentAction::class)->execute($lossExpense, $this->user, [
            'amount' => 120,
            'method' => 'bank_transfer',
            'reference' => 'SUP-FX-LOSS',
            'paid_at' => '2026-11-05',
            'gbp_exchange_rate' => '0.90000000',
            'exchange_rate_source' => 'bank_statement',
            'exchange_rate_date' => '2026-11-05',
        ]);
        $lossJournal = $lossPayment->journalEntries()->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame('-6.00', $lossPayment->realised_fx_gbp);
        $this->assertSame('6.00', $lossJournal->lines->firstWhere('ledgerAccount.code', '7100')->debit_gbp);
        $this->assertSame('108.00', $lossJournal->lines->firstWhere('ledgerAccount.code', '1100')->credit_gbp);
    }

    public function test_supplier_cash_refund_posts_realised_fx_gain(): void
    {
        $expense = $this->postedExpense([
            'currency' => 'EUR',
            'gbp_exchange_rate' => '0.85000000',
            'exchange_rate_source' => 'bill_rate',
            'exchange_rate_date' => '2026-11-01',
        ]);
        app(RecordExpensePaymentAction::class)->execute($expense, $this->user, [
            'amount' => 120,
            'method' => 'bank_transfer',
            'reference' => 'SUP-PAID-FX',
            'paid_at' => '2026-11-03',
            'gbp_exchange_rate' => '0.85000000',
        ]);
        $credit = app(CreatePurchaseCreditNoteAction::class)->execute($expense, $this->user, [
            'reason' => 'Supplier cash refund with FX',
            'issue_date' => '2026-11-04',
            'tax_point' => '2026-11-04',
            'items' => [[
                'expense_item_id' => $expense->items->first()->id,
                'quantity' => 0.5,
            ]],
        ]);
        $credit = app(IssuePurchaseCreditNoteAction::class)->execute($credit, $this->user);
        $refund = app(RecordPurchaseCreditRefundAction::class)->execute($credit, $this->user, [
            'amount' => 60,
            'method' => 'bank_transfer',
            'reference' => 'SUP-REF-FX',
            'received_at' => '2026-11-05',
            'gbp_exchange_rate' => '0.90000000',
            'exchange_rate_source' => 'bank_statement',
            'exchange_rate_date' => '2026-11-05',
        ]);
        $journal = $refund->journalEntries()->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame('54.00', $refund->amount_gbp);
        $this->assertSame('51.00', $refund->carrying_amount_gbp);
        $this->assertSame('3.00', $refund->realised_fx_gbp);
        $this->assertSame('54.00', $journal->lines->firstWhere('ledgerAccount.code', '1100')->debit_gbp);
        $this->assertSame('51.00', $journal->lines->firstWhere('ledgerAccount.code', '2100')->credit_gbp);
        $this->assertSame('3.00', $journal->lines->firstWhere('ledgerAccount.code', '4300')->credit_gbp);
    }

    public function test_closed_period_rolls_back_payment_and_settlement_status(): void
    {
        $invoice = $this->issuedInvoice();
        $this->period->update(['status' => AccountingPeriod::STATUS_CLOSED]);

        try {
            app(RecordInvoicePaymentAction::class)->execute($invoice, $this->user, [
                'amount' => 40,
                'method' => 'bank_transfer',
                'reference' => 'BLOCKED-001',
                'paid_at' => '2026-11-05',
            ]);
            $this->fail('A closed period should reject payment posting.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('is closed', $exception->getMessage());
        }

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame('sent', $invoice->fresh()->status);
        $this->assertSame('0.00', $invoice->fresh()->amount_paid);
        $this->assertDatabaseCount('journal_entries', 1);
    }

    /** @param array<string, mixed> $overrides */
    private function issuedInvoice(array $overrides = []): Invoice
    {
        $invoice = Invoice::create([
            'business_id' => $this->business->id,
            'number' => 'INV-PAY-'.uniqid(),
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'due_date' => '2026-12-01',
            ...$overrides,
        ]);
        InvoiceItem::create([
            'business_id' => $this->business->id,
            'invoice_id' => $invoice->id,
            'tax_code_id' => $this->taxCode('S20')->id,
            'description' => 'Payment test service',
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        return app(IssueInvoiceAction::class)->execute($invoice, $this->user);
    }

    /** @param array<string, mixed> $overrides */
    private function postedExpense(array $overrides = []): Expense
    {
        $expense = app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'BILL-PAY-'.uniqid(),
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'business_purpose' => 'Payment posting test',
            ...$overrides,
            'items' => [[
                'description' => 'Supplier service',
                'category' => 'software',
                'quantity' => 1,
                'unit_price' => 100,
                'tax_code_id' => $this->taxCode('S20')->id,
            ]],
        ]);
        $expense = app(ApproveExpenseAction::class)->execute($expense, $this->user);

        return app(PostExpenseAction::class)->execute($expense, $this->user);
    }

    private function taxCode(string $code): TaxCode
    {
        return TaxCode::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }
}
