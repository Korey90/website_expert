<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\ReverseJournalEntryAction;
use App\Filament\Pages\VatDetailPage;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\PurchaseCreditNote;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use App\Services\Finance\VatDetailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VatDetailReportTest extends TestCase
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
            'name' => 'VAT Detail Test Business',
            'slug' => 'vat-detail-test-business',
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
        $this->createPeriod($this->business, $this->user);

        foreach (['access_admin_panel', 'view_finance', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'post_finance']);

        $this->client = Client::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'company_name' => 'Causeway Customer Ltd',
            'status' => 'active',
            'source' => 'referral',
            'country' => 'GB',
            'currency' => 'GBP',
        ]);
        $this->supplier = Supplier::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => 'Lagan Supplier Ltd',
            'default_currency' => 'GBP',
            'status' => Supplier::STATUS_ACTIVE,
        ]);
    }

    public function test_report_details_sales_purchases_credits_tax_treatments_and_fx_in_pennies(): void
    {
        $standardInvoice = $this->invoice('INV-S20');
        $this->postVatJournal('2026-07-05', 'vat:sales-standard', $standardInvoice, [
            $this->line('1200', '120.00', '0.00', null, clientId: $this->client->id),
            $this->line('4100', '0.00', '100.00', 'S20', clientId: $this->client->id),
            $this->line('2200', '0.00', '20.00', 'S20', clientId: $this->client->id),
        ]);

        $reducedInvoice = $this->invoice('INV-R5-EUR', 'EUR');
        $this->postVatJournal('2026-07-06', 'vat:sales-reduced', $reducedInvoice, [
            $this->line('1200', '52.50', '0.00', null, clientId: $this->client->id, currency: 'EUR'),
            $this->line('4100', '0.00', '50.00', 'R5', clientId: $this->client->id, currency: 'EUR'),
            $this->line('2200', '0.00', '2.50', 'R5', clientId: $this->client->id, currency: 'EUR'),
        ]);

        foreach ([['INV-Z0', 'Z0', '30.00'], ['INV-EXEMPT', 'EXEMPT', '40.00'], ['INV-OUTSIDE', 'OUTSIDE', '50.00']] as [$number, $taxCode, $net]) {
            $invoice = $this->invoice($number);
            $this->postVatJournal('2026-07-07', 'vat:'.strtolower($number), $invoice, [
                $this->line('1200', $net, '0.00', null, clientId: $this->client->id),
                $this->line('4100', '0.00', $net, $taxCode, clientId: $this->client->id),
            ]);
        }

        $credit = $this->creditNote($standardInvoice, 'CN-S20');
        $this->postVatJournal('2026-07-08', 'vat:sales-credit', $credit, [
            $this->line('4100', '10.00', '0.00', 'S20', clientId: $this->client->id),
            $this->line('2200', '2.00', '0.00', 'S20', clientId: $this->client->id),
            $this->line('1200', '0.00', '12.00', null, clientId: $this->client->id),
        ]);

        $expense = $this->expense('SUP-100');
        $this->postVatJournal('2026-07-09', 'vat:purchase-standard', $expense, [
            $this->line('6200', '80.00', '0.00', 'S20', supplierId: $this->supplier->id),
            $this->line('1300', '16.00', '0.00', 'S20', supplierId: $this->supplier->id),
            $this->line('2100', '0.00', '96.00', null, supplierId: $this->supplier->id),
        ]);

        $purchaseCredit = $this->purchaseCreditNote($expense, 'PCN-S20');
        $this->postVatJournal('2026-07-10', 'vat:purchase-credit', $purchaseCredit, [
            $this->line('2100', '24.00', '0.00', null, supplierId: $this->supplier->id),
            $this->line('6200', '0.00', '20.00', 'S20', supplierId: $this->supplier->id),
            $this->line('1300', '0.00', '4.00', 'S20', supplierId: $this->supplier->id),
        ]);

        $this->client->delete();
        $this->supplier->delete();

        $report = app(VatDetailService::class)->report($this->business->id, '2026-07-01', '2026-07-31');
        $rows = $report['rows']->keyBy('reference');
        $codes = $report['tax_codes']->keyBy('code');

        $this->assertSame('2026-07-01', $report['date_from']);
        $this->assertSame('2026-07-31', $report['date_to']);
        $this->assertCount(8, $rows);
        $this->assertSame(260.0, $report['totals']->net_sales_gbp);
        $this->assertSame(60.0, $report['totals']->net_purchases_gbp);
        $this->assertSame(20.5, $report['totals']->output_vat_gbp);
        $this->assertSame(12.0, $report['totals']->input_vat_gbp);
        $this->assertSame(8.5, $report['totals']->net_vat_gbp);
        $this->assertSame(20.5, $report['totals']->output_control_gbp);
        $this->assertSame(12.0, $report['totals']->input_control_gbp);
        $this->assertSame(0.0, $report['totals']->output_difference_gbp);
        $this->assertSame(0.0, $report['totals']->input_difference_gbp);
        $this->assertTrue($report['totals']->is_reconciled);
        $this->assertSame(8, $report['totals']->document_count);
        $this->assertSame(0, $report['totals']->adjustment_count);
        $this->assertSame(5, $report['totals']->tax_code_count);

        $this->assertSame('Causeway Customer Ltd', $rows['INV-S20']->counterparty);
        $this->assertSame('EUR', $rows['INV-R5-EUR']->currency);
        $this->assertSame('sales_credit_note', $rows['CN-S20']->kind);
        $this->assertSame(-10.0, $rows['CN-S20']->net_sales_gbp);
        $this->assertSame(-2.0, $rows['CN-S20']->output_vat_gbp);
        $this->assertSame('Lagan Supplier Ltd', $rows['SUP-100']->counterparty);
        $this->assertSame('purchase_credit_note', $rows['PCN-S20']->kind);
        $this->assertSame(-20.0, $rows['PCN-S20']->net_purchases_gbp);
        $this->assertSame(-4.0, $rows['PCN-S20']->input_vat_gbp);
        $this->assertSame(90.0, $codes['S20']->net_sales_gbp);
        $this->assertSame(60.0, $codes['S20']->net_purchases_gbp);
        $this->assertSame(18.0, $codes['S20']->output_vat_gbp);
        $this->assertSame(12.0, $codes['S20']->input_vat_gbp);
        $this->assertSame([1, 4, 6, 7], $codes['S20']->mapping_boxes);
        $this->assertSame([1], $codes['S20']->vat_return_mapping['output_vat']);
        $this->assertSame([4], $codes['S20']->vat_return_mapping['input_vat']);
        $this->assertSame(30.0, $codes['Z0']->net_sales_gbp);
        $this->assertSame(0.0, $codes['EXEMPT']->output_vat_gbp);
        $this->assertSame([], $codes['OUTSIDE']->mapping_boxes);
    }

    public function test_reverse_charge_reversal_manual_unknown_and_custom_control_accounts_reconcile(): void
    {
        $reverseChargeExpense = $this->expense('SUP-RC20');
        $original = $this->postVatJournal('2026-07-12', 'vat:reverse-charge', $reverseChargeExpense, [
            $this->line('6500', '100.00', '0.00', 'RC20', supplierId: $this->supplier->id),
            $this->line('1300', '20.00', '0.00', 'RC20', supplierId: $this->supplier->id),
            $this->line('2200', '0.00', '20.00', 'RC20', supplierId: $this->supplier->id),
            $this->line('2100', '0.00', '100.00', null, supplierId: $this->supplier->id),
        ]);
        app(ReverseJournalEntryAction::class)->execute($original, $this->user, 'Reverse VAT detail test', '2026-08-01');

        $customInput = $this->createAccount('1310', 'Other input VAT', 'asset', 'tax_receivable', 'debit');
        $customOutput = $this->createAccount('2210', 'Other output VAT', 'liability', 'tax_payable', 'credit');
        $this->postVatJournal('2026-07-15', 'vat:custom-input', null, [
            $this->line('6200', '25.00', '0.00', 'S20', supplierId: $this->supplier->id),
            $this->line((string) $customInput->code, '5.00', '0.00', 'S20', supplierId: $this->supplier->id),
            $this->line('2100', '0.00', '30.00', null, supplierId: $this->supplier->id),
        ]);
        $this->postVatJournal('2026-07-16', 'vat:unknown-output', null, [
            $this->line('6200', '3.00', '0.00'),
            $this->line((string) $customOutput->code, '0.00', '3.00'),
        ]);

        [$otherBusiness, $otherUser] = $this->otherTenant();
        app(PostJournalEntryAction::class)->execute($otherBusiness, $otherUser, [
            'entry_date' => '2026-07-20',
            'description' => 'Private output VAT',
            'idempotency_key' => 'private:vat-detail',
            'source_snapshot' => ['private' => true],
            'lines' => [
                $this->tenantLine($otherBusiness, '6200', '999.00', '0.00'),
                $this->tenantLine($otherBusiness, '2200', '0.00', '999.00'),
            ],
        ]);

        $july = app(VatDetailService::class)->report($this->business->id, '2026-07-01', '2026-07-31');
        $rc = $july['rows']->firstWhere('reference', 'SUP-RC20');
        $unknown = $july['rows']->firstWhere('tax_code', 'UNKNOWN');

        $this->assertSame(100.0, $rc->net_purchases_gbp);
        $this->assertSame(20.0, $rc->output_vat_gbp);
        $this->assertSame(20.0, $rc->input_vat_gbp);
        $this->assertSame(125.0, $july['totals']->net_purchases_gbp);
        $this->assertSame(23.0, $july['totals']->output_vat_gbp);
        $this->assertSame(25.0, $july['totals']->input_vat_gbp);
        $this->assertSame(-2.0, $july['totals']->net_vat_gbp);
        $this->assertTrue($july['totals']->is_reconciled);
        $this->assertSame(2, $july['totals']->adjustment_count);
        $this->assertSame(1, $july['totals']->unknown_tax_code_count);
        $this->assertTrue($unknown->is_adjustment);
        $this->assertSame(3.0, $unknown->output_vat_gbp);
        $this->assertStringNotContainsString('999', json_encode($july, JSON_THROW_ON_ERROR));

        $august = app(VatDetailService::class)->report($this->business->id, '2026-08-01', '2026-08-31');
        $reversal = $august['rows']->first();
        $this->assertTrue($reversal->is_reversal);
        $this->assertSame('SUP-RC20', $reversal->reference);
        $this->assertSame(-100.0, $reversal->net_purchases_gbp);
        $this->assertSame(-20.0, $reversal->output_vat_gbp);
        $this->assertSame(-20.0, $reversal->input_vat_gbp);
        $this->assertTrue($august['totals']->is_reconciled);
    }

    public function test_empty_report_and_strict_dates_are_supported(): void
    {
        $report = app(VatDetailService::class)->report($this->business->id, '2026-09-01', '2026-09-30');

        $this->assertTrue($report['rows']->isEmpty());
        $this->assertTrue($report['tax_codes']->isEmpty());
        $this->assertSame(0.0, $report['totals']->net_sales_gbp);
        $this->assertSame(0.0, $report['totals']->output_vat_gbp);
        $this->assertSame(0.0, $report['totals']->input_vat_gbp);
        $this->assertTrue($report['totals']->is_reconciled);

        try {
            app(VatDetailService::class)->report($this->business->id, '01/09/2026', '2026-09-30');
            $this->fail('An invalid date format should fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('YYYY-MM-DD', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be after');
        app(VatDetailService::class)->report($this->business->id, '2026-10-01', '2026-09-30');
    }

    public function test_authorized_user_can_open_page_and_export_private_csv(): void
    {
        $invoice = $this->invoice('INV-EXPORT');
        $this->postVatJournal('2026-07-05', 'vat:export', $invoice, [
            $this->line('1200', '120.00', '0.00', null, clientId: $this->client->id),
            $this->line('4100', '0.00', '100.00', 'S20', clientId: $this->client->id),
            $this->line('2200', '0.00', '20.00', 'S20', clientId: $this->client->id),
        ]);

        $this->actingAs($this->user)
            ->get(VatDetailPage::getUrl())
            ->assertOk()
            ->assertSee(__('finance.vat_detail.title'))
            ->assertSee('INV-EXPORT')
            ->assertSee('Causeway Customer Ltd');

        $response = $this->get(route('admin.finance.vat-detail.csv', [
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]));
        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('cache-control', 'no-store, private');
        $content = $response->streamedContent();

        $this->assertStringContainsString('VAT DETAIL', $content);
        $this->assertStringContainsString('INV-EXPORT', $content);
        $this->assertStringContainsString('Causeway Customer Ltd', $content);
        $this->assertStringContainsString('S20', $content);
        $this->assertStringContainsString('100', $content);
        $this->assertStringContainsString('20', $content);
    }

    public function test_permission_and_range_validation_protect_page_and_export(): void
    {
        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $member->id,
            'role' => 'member',
            'is_active' => true,
        ]);
        $member->givePermissionTo('access_admin_panel');

        $this->actingAs($member)->get(VatDetailPage::getUrl())->assertForbidden();
        $this->get(route('admin.finance.vat-detail.csv', [
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]))->assertForbidden();

        $this->actingAs($this->user)
            ->get(route('admin.finance.vat-detail.csv', [
                'date_from' => '2026-07-31',
                'date_to' => '2026-07-01',
            ]))
            ->assertSessionHasErrors('date_to');
        $this->get(route('admin.finance.vat-detail.csv'))->assertSessionHasErrors(['date_from', 'date_to']);
    }

    private function invoice(string $number, string $currency = 'GBP'): Invoice
    {
        return Invoice::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => $number,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'status' => 'sent',
            'currency' => $currency,
            'subtotal' => 0,
            'vat_amount' => 0,
            'total' => 0,
            'amount_paid' => 0,
            'amount_due' => 0,
            'issue_date' => '2026-07-01',
            'tax_point' => '2026-07-01',
            'due_date' => '2026-07-31',
            'gbp_exchange_rate' => $currency === 'GBP' ? 1 : 0.85,
            'exchange_rate_source' => $currency === 'GBP' ? 'identity' : 'test',
            'exchange_rate_date' => '2026-07-01',
            'subtotal_gbp' => 0,
            'vat_amount_gbp' => 0,
            'total_gbp' => 0,
        ]);
    }

    private function expense(string $reference): Expense
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
            'exchange_rate_date' => '2026-07-01',
            'issue_date' => '2026-07-01',
            'tax_point' => '2026-07-01',
            'due_date' => '2026-07-31',
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

    private function creditNote(Invoice $invoice, string $number): CreditNote
    {
        return CreditNote::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => $number,
            'invoice_id' => $invoice->id,
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'status' => CreditNote::STATUS_ISSUED,
            'currency' => 'GBP',
            'reason' => 'VAT detail test credit',
            'issue_date' => '2026-07-08',
            'tax_point' => '2026-07-08',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-07-08',
            'subtotal' => 10,
            'vat_amount' => 2,
            'total' => 12,
            'subtotal_gbp' => 10,
            'vat_amount_gbp' => 2,
            'total_gbp' => 12,
            'allocated_amount' => 0,
        ]);
    }

    private function purchaseCreditNote(Expense $expense, string $number): PurchaseCreditNote
    {
        return PurchaseCreditNote::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => $number,
            'expense_id' => $expense->id,
            'supplier_id' => $this->supplier->id,
            'created_by' => $this->user->id,
            'status' => PurchaseCreditNote::STATUS_ISSUED,
            'currency' => 'GBP',
            'reason' => 'VAT detail test credit',
            'issue_date' => '2026-07-10',
            'tax_point' => '2026-07-10',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-07-10',
            'subtotal' => 20,
            'vat_amount' => 4,
            'total' => 24,
            'subtotal_gbp' => 20,
            'vat_amount_gbp' => 4,
            'total_gbp' => 24,
            'allocated_amount' => 0,
            'refunded_amount' => 0,
        ]);
    }

    /** @param list<array<string, mixed>> $lines */
    private function postVatJournal(string $date, string $key, ?Model $source, array $lines): JournalEntry
    {
        return app(PostJournalEntryAction::class)->execute($this->business, $this->user, [
            'entry_date' => $date,
            'description' => 'VAT detail '.$key,
            'idempotency_key' => $key,
            ...($source ? ['source' => $source] : []),
            'source_snapshot' => ['test' => true],
            'lines' => $lines,
        ]);
    }

    /** @return array<string, mixed> */
    private function line(
        string $accountCode,
        string $debit,
        string $credit,
        ?string $taxCode = null,
        ?int $clientId = null,
        ?int $supplierId = null,
        string $currency = 'GBP',
    ): array {
        return array_filter([
            'ledger_account_id' => $this->account($accountCode)->id,
            'tax_code_id' => $taxCode ? $this->taxCode($taxCode)->id : null,
            'client_id' => $clientId,
            'supplier_id' => $supplierId,
            'debit_gbp' => $debit,
            'credit_gbp' => $credit,
            'original_currency' => $currency,
            'original_amount' => $currency === 'GBP' ? null : ($debit !== '0.00' ? $debit : $credit),
            'exchange_rate' => $currency === 'GBP' ? '1.00000000' : '0.85000000',
        ], fn (mixed $value): bool => $value !== null);
    }

    /** @return array<string, mixed> */
    private function tenantLine(Business $business, string $code, string $debit, string $credit): array
    {
        return [
            'ledger_account_id' => LedgerAccount::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('code', $code)
                ->value('id'),
            'debit_gbp' => $debit,
            'credit_gbp' => $credit,
        ];
    }

    private function taxCode(string $code): TaxCode
    {
        return TaxCode::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function account(string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
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

    private function createPeriod(Business $business, User $user): AccountingPeriod
    {
        return AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'name' => 'FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $user->id,
        ]);
    }

    /** @return array{Business, User} */
    private function otherTenant(): array
    {
        $business = Business::create([
            'name' => 'Private VAT Business',
            'slug' => 'private-vat-'.str()->lower(str()->random(6)),
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
        $this->createPeriod($business, $user);

        return [$business, $user];
    }
}
