<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\ReverseJournalEntryAction;
use App\Filament\Pages\AgedReceivablesPayablesPage;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\PurchaseCreditNote;
use App\Models\PurchaseCreditRefund;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Finance\AgedReceivablesPayablesService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AgedReceivablesPayablesReportTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Client $client;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Ageing Test Business',
            'slug' => 'ageing-test-business',
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
        AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => 'FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->user->id,
        ]);

        foreach (['access_admin_panel', 'view_finance', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'post_finance']);

        $this->client = Client::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'company_name' => 'North Sea Client Ltd',
            'status' => 'active',
            'source' => 'referral',
            'country' => 'GB',
            'currency' => 'GBP',
        ]);
        $this->supplier = Supplier::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => 'Belfast Supplier Ltd',
            'default_currency' => 'GBP',
            'status' => Supplier::STATUS_ACTIVE,
        ]);
    }

    public function test_report_ages_historical_ledger_balances_and_reconciles_every_bucket(): void
    {
        $arCurrent = $this->invoice('AR-CURRENT', '2026-06-01', '2026-07-15');
        $arOne = $this->invoice('AR-1-30', '2026-05-01', '2026-06-15');
        $arThirty = $this->invoice('AR-31-60', '2026-04-01', '2026-05-15');
        $arSixty = $this->invoice('AR-61-90', '2026-03-01', '2026-04-15');
        $arNinety = $this->invoice('AR-91', '2026-02-01', '2026-03-01');
        $arCredit = $this->invoice('AR-CREDIT', '2026-06-01', '2026-07-30');
        $arReversed = $this->invoice('AR-REVERSED', '2026-01-01', '2026-02-01');

        foreach ([
            [$arCurrent, '1000.00'], [$arOne, '300.00'], [$arThirty, '400.00'],
            [$arSixty, '500.00'], [$arNinety, '600.00'], [$arCredit, '50.00'],
        ] as [$invoice, $amount]) {
            $this->postControl($invoice->issue_date->toDateString(), 'invoice-'.$invoice->id, $invoice, '1200', $amount, '0.00', $this->client->id);
        }
        $reversed = $this->postControl('2026-01-01', 'invoice-reversed', $arReversed, '1200', '25.00', '0.00', $this->client->id);
        app(ReverseJournalEntryAction::class)->execute($reversed, $this->user, 'Reverse ageing test invoice', '2026-06-10');

        $paymentBefore = $this->payment($arCurrent, 'PAY-BEFORE', '2026-06-20', 200);
        $this->postControl('2026-06-20', 'payment-before', $paymentBefore, '1200', '0.00', '200.00', $this->client->id);
        $paymentAfter = $this->payment($arCurrent, 'PAY-AFTER', '2026-07-01', 100);
        $this->postControl('2026-07-01', 'payment-after', $paymentAfter, '1200', '0.00', '100.00', $this->client->id);
        $arCreditNote = $this->creditNote($arOne, 'CN-AR-1', 50);
        $this->postControl('2026-06-16', 'credit-ar-one', $arCreditNote, '1200', '0.00', '50.00', $this->client->id);
        $arOverCredit = $this->creditNote($arCredit, 'CN-AR-CREDIT', 80);
        $this->postControl('2026-06-20', 'credit-ar-negative', $arOverCredit, '1200', '0.00', '80.00', $this->client->id);
        $this->postControl('2026-02-01', 'manual-ar', null, '1200', '20.00', '0.00', $this->client->id);

        $apCurrent = $this->expense('AP-CURRENT', '2026-06-01', '2026-07-10');
        $apOne = $this->expense('AP-1-30', '2026-05-01', '2026-06-10');
        $apThirty = $this->expense('AP-31-60', '2026-04-01', '2026-05-10');
        $apSixty = $this->expense('AP-61-90', '2026-03-01', '2026-04-10');
        $apNinety = $this->expense('AP-91', '2026-02-01', '2026-03-10');
        $apCredit = $this->expense('AP-CREDIT', '2026-06-01', '2026-07-20');

        foreach ([
            [$apCurrent, '1000.00'], [$apOne, '200.00'], [$apThirty, '300.00'],
            [$apSixty, '400.00'], [$apNinety, '500.00'], [$apCredit, '50.00'],
        ] as [$expense, $amount]) {
            $this->postControl($expense->issue_date->toDateString(), 'expense-'.$expense->id, $expense, '2100', '0.00', $amount, $this->supplier->id);
        }

        $expensePayment = $this->expensePayment($apCurrent, 'EPAY-BEFORE', '2026-06-20', 300);
        $this->postControl('2026-06-20', 'expense-payment-before', $expensePayment, '2100', '300.00', '0.00', $this->supplier->id);
        $expensePaymentAfter = $this->expensePayment($apCurrent, 'EPAY-AFTER', '2026-07-02', 100);
        $this->postControl('2026-07-02', 'expense-payment-after', $expensePaymentAfter, '2100', '100.00', '0.00', $this->supplier->id);
        $apCreditOne = $this->purchaseCredit($apOne, 'PCN-ONE', 50);
        $this->postControl('2026-06-12', 'purchase-credit-one', $apCreditOne, '2100', '50.00', '0.00', $this->supplier->id);
        $apCreditThirty = $this->purchaseCredit($apThirty, 'PCN-THIRTY', 50);
        $this->postControl('2026-06-01', 'purchase-credit-thirty', $apCreditThirty, '2100', '50.00', '0.00', $this->supplier->id);
        $refund = $this->purchaseRefund($apCreditThirty, $apThirty, 20);
        $this->postControl('2026-06-05', 'purchase-refund', $refund, '2100', '0.00', '20.00', $this->supplier->id);
        $apOverCredit = $this->purchaseCredit($apCredit, 'PCN-NEGATIVE', 80);
        $this->postControl('2026-06-20', 'purchase-credit-negative', $apOverCredit, '2100', '80.00', '0.00', $this->supplier->id);
        $this->postControl('2026-02-01', 'manual-ap', null, '2100', '0.00', '15.00', $this->supplier->id);

        $this->client->delete();
        $this->supplier->delete();

        $report = app(AgedReceivablesPayablesService::class)->report($this->business->id, '2026-06-30');
        $sections = $report['sections']->keyBy('key');
        $receivables = $sections['receivables'];
        $payables = $sections['payables'];

        $this->assertSame('2026-06-30', $report['as_of']);
        $this->assertSame(['current', 'one_to_thirty', 'thirty_one_to_sixty', 'sixty_one_to_ninety', 'ninety_one_plus'], $report['buckets']);
        $this->assertSame(770.0, $receivables->totals->current_gbp);
        $this->assertSame(250.0, $receivables->totals->one_to_thirty_gbp);
        $this->assertSame(400.0, $receivables->totals->thirty_one_to_sixty_gbp);
        $this->assertSame(500.0, $receivables->totals->sixty_one_to_ninety_gbp);
        $this->assertSame(620.0, $receivables->totals->ninety_one_plus_gbp);
        $this->assertSame(2540.0, $receivables->totals->total_gbp);
        $this->assertSame(2540.0, $receivables->totals->control_balance_gbp);
        $this->assertSame(0.0, $receivables->totals->difference_gbp);
        $this->assertTrue($receivables->totals->is_reconciled);
        $this->assertSame(6, $receivables->totals->document_count);
        $this->assertSame(1, $receivables->totals->adjustment_count);
        $this->assertSame(-30.0, $receivables->rows->firstWhere('reference', 'AR-CREDIT')->balance_gbp);
        $this->assertNull($receivables->rows->firstWhere('reference', 'AR-REVERSED'));
        $this->assertSame('North Sea Client Ltd', $receivables->rows->first()->counterparty);

        $this->assertSame(670.0, $payables->totals->current_gbp);
        $this->assertSame(150.0, $payables->totals->one_to_thirty_gbp);
        $this->assertSame(270.0, $payables->totals->thirty_one_to_sixty_gbp);
        $this->assertSame(400.0, $payables->totals->sixty_one_to_ninety_gbp);
        $this->assertSame(515.0, $payables->totals->ninety_one_plus_gbp);
        $this->assertSame(2005.0, $payables->totals->total_gbp);
        $this->assertSame(2005.0, $payables->totals->control_balance_gbp);
        $this->assertTrue($payables->totals->is_reconciled);
        $this->assertSame(6, $payables->totals->document_count);
        $this->assertSame(1, $payables->totals->adjustment_count);
        $this->assertSame(-30.0, $payables->rows->firstWhere('reference', 'AP-CREDIT')->balance_gbp);
        $this->assertSame('Belfast Supplier Ltd', $payables->rows->first()->counterparty);
    }

    public function test_empty_report_reconciles_and_invalid_date_is_rejected(): void
    {
        $report = app(AgedReceivablesPayablesService::class)->report($this->business->id, '2026-06-30');

        foreach ($report['sections'] as $section) {
            $this->assertTrue($section->rows->isEmpty());
            $this->assertSame(0.0, $section->totals->total_gbp);
            $this->assertSame(0.0, $section->totals->control_balance_gbp);
            $this->assertSame(0.0, $section->totals->difference_gbp);
            $this->assertTrue($section->totals->is_reconciled);
        }

        $this->expectException(InvalidArgumentException::class);
        app(AgedReceivablesPayablesService::class)->report($this->business->id, '30/06/2026');
    }

    public function test_custom_control_accounts_and_foreign_tenant_are_isolated(): void
    {
        $customReceivable = $this->createAccount('1210', 'Other debtors', 'asset', 'accounts_receivable', 'debit');
        $customPayable = $this->createAccount('2110', 'Other creditors', 'liability', 'accounts_payable', 'credit');
        $this->postControl('2026-06-01', 'custom-ar', null, (string) $customReceivable->code, '12.34', '0.00', $this->client->id);
        $this->postControl('2026-06-01', 'custom-ap', null, (string) $customPayable->code, '0.00', '56.78', $this->supplier->id);

        [$otherBusiness, $otherUser] = $this->otherTenant();
        $otherPeriod = AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Private FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $otherUser->id,
        ]);
        $this->assertNotNull($otherPeriod);
        app(PostJournalEntryAction::class)->execute($otherBusiness, $otherUser, [
            'entry_date' => '2026-06-01',
            'description' => 'Private control balance',
            'idempotency_key' => 'private-ageing',
            'source_snapshot' => ['private' => true],
            'lines' => [
                ['ledger_account_id' => $this->accountFor($otherBusiness, '1200')->id, 'debit_gbp' => '999.00', 'credit_gbp' => '0.00'],
                ['ledger_account_id' => $this->accountFor($otherBusiness, '4100')->id, 'debit_gbp' => '0.00', 'credit_gbp' => '999.00'],
            ],
        ]);

        $sections = app(AgedReceivablesPayablesService::class)
            ->report($this->business->id, '2026-06-30')['sections']
            ->keyBy('key');

        $this->assertSame(12.34, $sections['receivables']->totals->total_gbp);
        $this->assertSame(56.78, $sections['payables']->totals->total_gbp);
        $this->assertSame(1, $sections['receivables']->totals->control_account_count);
        $this->assertSame(1, $sections['payables']->totals->control_account_count);
        $this->assertStringNotContainsString('999', json_encode($sections, JSON_THROW_ON_ERROR));
    }

    public function test_authorized_user_can_open_page_and_export_tenant_csv(): void
    {
        $invoice = $this->invoice('AR-EXPORT', '2026-06-01', '2026-06-15');
        $this->postControl('2026-06-01', 'export-ar', $invoice, '1200', '75.00', '0.00', $this->client->id);

        $this->actingAs($this->user)
            ->get(AgedReceivablesPayablesPage::getUrl())
            ->assertOk()
            ->assertSee(__('finance.aged_receivables_payables.title'))
            ->assertSee('North Sea Client Ltd');

        $response = $this->get(route('admin.finance.aged-receivables-payables.csv', ['as_of' => '2026-06-30']));
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('cache-control', 'no-store, private');
        $content = $response->streamedContent();

        $this->assertStringContainsString('AGED RECEIVABLES AND PAYABLES', $content);
        $this->assertStringContainsString('North Sea Client Ltd', $content);
        $this->assertStringContainsString('AR-EXPORT', $content);
        $this->assertStringContainsString('75', $content);
    }

    public function test_permission_and_date_validation_protect_page_and_export(): void
    {
        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $member->id,
            'role' => 'member',
            'is_active' => true,
        ]);
        $member->givePermissionTo('access_admin_panel');

        $this->actingAs($member)->get(AgedReceivablesPayablesPage::getUrl())->assertForbidden();
        $this->get(route('admin.finance.aged-receivables-payables.csv', ['as_of' => '2026-06-30']))->assertForbidden();

        $this->actingAs($this->user)
            ->get(route('admin.finance.aged-receivables-payables.csv', ['as_of' => '30/06/2026']))
            ->assertSessionHasErrors('as_of');
        $this->get(route('admin.finance.aged-receivables-payables.csv'))->assertSessionHasErrors('as_of');
    }

    private function invoice(string $number, string $issueDate, string $dueDate): Invoice
    {
        return Invoice::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => $number,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'status' => 'sent',
            'currency' => 'GBP',
            'subtotal' => 0,
            'vat_amount' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'amount_due' => 0,
            'issue_date' => $issueDate,
            'tax_point' => $issueDate,
            'due_date' => $dueDate,
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => $issueDate,
            'subtotal_gbp' => 0,
            'vat_amount_gbp' => 0,
            'total_gbp' => 0,
        ]);
    }

    private function expense(string $reference, string $issueDate, string $dueDate): Expense
    {
        return Expense::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
            'supplier_invoice_number' => $reference,
            'status' => Expense::STATUS_POSTED,
            'currency' => 'GBP',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => $issueDate,
            'issue_date' => $issueDate,
            'tax_point' => $issueDate,
            'due_date' => $dueDate,
            'subtotal' => 0,
            'vat_amount' => 0,
            'total' => 0,
            'subtotal_gbp' => 0,
            'vat_amount_gbp' => 0,
            'total_gbp' => 0,
            'amount_paid' => 0,
            'amount_due' => 0,
        ]);
    }

    private function payment(Invoice $invoice, string $reference, string $date, float $amount): Payment
    {
        return Payment::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'invoice_id' => $invoice->id,
            'created_by' => $this->user->id,
            'amount' => $amount,
            'amount_gbp' => $amount,
            'carrying_amount_gbp' => $amount,
            'realised_fx_gbp' => 0,
            'currency' => 'GBP',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => $date,
            'method' => 'bank_transfer',
            'status' => 'completed',
            'reference' => $reference,
            'paid_at' => $date,
        ]);
    }

    private function expensePayment(Expense $expense, string $reference, string $date, float $amount): ExpensePayment
    {
        return ExpensePayment::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'expense_id' => $expense->id,
            'created_by' => $this->user->id,
            'amount' => $amount,
            'amount_gbp' => $amount,
            'carrying_amount_gbp' => $amount,
            'realised_fx_gbp' => 0,
            'currency' => 'GBP',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => $date,
            'method' => 'bank_transfer',
            'reference' => $reference,
            'paid_at' => $date,
        ]);
    }

    private function creditNote(Invoice $invoice, string $number, float $amount): CreditNote
    {
        return CreditNote::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => $number,
            'invoice_id' => $invoice->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'status' => CreditNote::STATUS_ISSUED,
            'currency' => 'GBP',
            'reason' => 'Ageing test credit',
            'issue_date' => '2026-06-01',
            'tax_point' => '2026-06-01',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-06-01',
            'subtotal' => $amount,
            'vat_amount' => 0,
            'total' => $amount,
            'subtotal_gbp' => $amount,
            'vat_amount_gbp' => 0,
            'total_gbp' => $amount,
            'allocated_amount' => 0,
        ]);
    }

    private function purchaseCredit(Expense $expense, string $number, float $amount): PurchaseCreditNote
    {
        return PurchaseCreditNote::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => $number,
            'expense_id' => $expense->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
            'status' => PurchaseCreditNote::STATUS_ISSUED,
            'currency' => 'GBP',
            'reason' => 'Ageing test credit',
            'issue_date' => '2026-06-01',
            'tax_point' => '2026-06-01',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-06-01',
            'subtotal' => $amount,
            'vat_amount' => 0,
            'total' => $amount,
            'subtotal_gbp' => $amount,
            'vat_amount_gbp' => 0,
            'total_gbp' => $amount,
            'allocated_amount' => 0,
            'refunded_amount' => 0,
        ]);
    }

    private function purchaseRefund(PurchaseCreditNote $credit, Expense $expense, float $amount): PurchaseCreditRefund
    {
        return PurchaseCreditRefund::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'purchase_credit_note_id' => $credit->id,
            'expense_id' => $expense->id,
            'received_by' => $this->user->id,
            'amount' => $amount,
            'amount_gbp' => $amount,
            'carrying_amount_gbp' => $amount,
            'realised_fx_gbp' => 0,
            'currency' => 'GBP',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-06-05',
            'method' => 'bank_transfer',
            'reference' => 'PCR-REFUND',
            'received_at' => '2026-06-05',
        ]);
    }

    private function postControl(
        string $date,
        string $key,
        ?Model $source,
        string $controlCode,
        string $debit,
        string $credit,
        ?int $counterpartyId,
    ): JournalEntry {
        $isReceivable = str_starts_with($controlCode, '12');
        $controlLine = [
            'ledger_account_id' => $this->account($controlCode)->id,
            'debit_gbp' => $debit,
            'credit_gbp' => $credit,
            $isReceivable ? 'client_id' : 'supplier_id' => $counterpartyId,
        ];
        $offsetLine = [
            'ledger_account_id' => $this->account($debit !== '0.00' ? '4100' : '6200')->id,
            'debit_gbp' => $credit,
            'credit_gbp' => $debit,
        ];

        return app(PostJournalEntryAction::class)->execute($this->business, $this->user, [
            'entry_date' => $date,
            'description' => 'Ageing test '.$key,
            'idempotency_key' => 'ageing:'.$key,
            ...($source ? ['source' => $source] : []),
            'source_snapshot' => ['test' => true],
            'lines' => [$controlLine, $offsetLine],
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

    private function account(string $code): LedgerAccount
    {
        return $this->accountFor($this->business, $code);
    }

    private function accountFor(Business $business, string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    /** @return array{Business, User} */
    private function otherTenant(): array
    {
        $business = Business::create([
            'name' => 'Private Ageing Business',
            'slug' => 'private-ageing-'.str()->lower(str()->random(6)),
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
        $user->givePermissionTo('post_finance');
        app(InitializeAccountingSetupAction::class)->execute($business, $user);

        return [$business, $user];
    }
}
