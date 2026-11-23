<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\ApproveExpenseAction;
use App\Actions\Finance\ClassifyUniversalCreditReportItemAction;
use App\Actions\Finance\CollectUniversalCreditSourcesAction;
use App\Actions\Finance\CreateBankAccountAction;
use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\CreateUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\CreateUniversalCreditManualEntryAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\IssueInvoiceAction;
use App\Actions\Finance\PostExpenseAction;
use App\Actions\Finance\RecordExpensePaymentAction;
use App\Actions\Finance\RecordInvoicePaymentAction;
use App\Actions\Finance\RefundInvoicePaymentAction;
use App\Actions\Finance\UpdateUniversalCreditProfileAction;
use App\Actions\Finance\VoidUniversalCreditManualEntryAction;
use App\Models\AccountingPeriod;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAllocation;
use App\Models\BankTransaction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\FixedAssetTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditReportItem;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UniversalCreditSourcesTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    private Client $client;

    private Supplier $supplier;

    private UniversalCreditAssessmentPeriod $ucPeriod;

    private BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('financial');
        Queue::fake();
        Carbon::setTestNow('2026-12-01 10:00:00');
        CarbonImmutable::setTestNow('2026-12-01 10:00:00');
        $this->business = Business::create([
            'name' => 'UC Source Sole Trader',
            'slug' => 'uc-source-sole-trader',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        foreach (['manage_finance', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->owner->givePermissionTo([
            'manage_finance',
            'post_finance',
            'view_universal_credit_reporting',
            'manage_universal_credit_reporting',
        ]);
        $this->actingAs($this->owner);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->owner);
        $this->business->accountingProfile()->withoutGlobalScopes()->update([
            'entity_type' => 'sole_trader',
            'vat_registered' => false,
        ]);
        AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => 'FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->owner->id,
        ]);
        $this->client = Client::create([
            'business_id' => $this->business->id,
            'company_name' => 'UC Customer Ltd',
            'status' => 'active',
        ]);
        $this->supplier = Supplier::factory()->for($this->business)->create();
        $cashAccount = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', '1100')
            ->firstOrFail();
        $this->bankAccount = app(CreateBankAccountAction::class)->execute($this->business, $this->owner, [
            'name' => 'UC account',
            'bank_name' => 'Test Bank',
            'account_number' => '12345678',
            'sort_code' => '12-34-56',
            'currency' => 'GBP',
            'ledger_account_id' => $cashAccount->id,
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
        app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, [
            'is_enabled' => true,
        ]);
        $this->ucPeriod = app(CreateUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->business,
            $this->owner,
            '2026-11-23',
            '2026-12-22',
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_it_collects_cash_events_on_their_actual_dates_and_is_idempotent(): void
    {
        $invoice = $this->issuedInvoice();
        $payment = app(RecordInvoicePaymentAction::class)->execute($invoice, $this->owner, [
            'amount' => 40,
            'method' => 'bank_transfer',
            'bank_account_id' => $this->bankAccount->id,
            'reference' => 'UC-RECEIPT-1',
            'paid_at' => '2026-11-25',
        ]);
        app(RefundInvoicePaymentAction::class)->execute($payment, $this->owner);
        $expense = $this->postedExpense();
        app(RecordExpensePaymentAction::class)->execute($expense, $this->owner, [
            'amount' => 30,
            'method' => 'card',
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'reference' => 'UC-OWNER-1',
            'paid_at' => '2026-12-02',
        ]);
        BankTransaction::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'bank_account_id' => $this->bankAccount->id,
            'provider_id' => 'UC-OPEN-1',
            'booking_date' => '2026-12-03',
            'amount' => '-12.00',
            'currency' => 'GBP',
            'reference' => 'Unmatched cash cost',
            'source_hash' => hash('sha256', 'UC-OPEN-1'),
            'reconciliation_status' => BankTransaction::STATUS_UNMATCHED,
        ]);

        $first = app(CollectUniversalCreditSourcesAction::class)->execute($this->ucPeriod, $this->owner);
        $second = app(CollectUniversalCreditSourcesAction::class)->execute($this->ucPeriod, $this->owner);

        $this->assertSame(4, $first->discovered);
        $this->assertSame(4, $first->created);
        $this->assertSame(0, $first->updated);
        $this->assertSame(4, $second->unchanged);
        $this->assertSame(4, $this->ucPeriod->reportItems()->count());
        $this->assertDatabaseHas('universal_credit_report_items', [
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'source_event_key' => 'receipt',
            'direction' => UniversalCreditReportItem::DIRECTION_INCOME,
            'cash_date' => '2026-11-25 00:00:00',
            'gross_amount_pence' => 4000,
            'vat_amount_pence' => 667,
            'vat_snapshot_source' => 'document',
        ]);
        $this->assertDatabaseHas('universal_credit_report_items', [
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'source_event_key' => 'refund',
            'direction' => UniversalCreditReportItem::DIRECTION_EXPENSE,
            'cash_date' => '2026-12-01 00:00:00',
        ]);
        $this->assertDatabaseHas('universal_credit_report_items', [
            'source_type' => ExpensePayment::class,
            'source_event_key' => 'payment',
            'gross_amount_pence' => 3000,
            'vat_amount_pence' => 500,
            'vat_snapshot_source' => 'document',
        ]);
        $this->assertDatabaseHas('universal_credit_report_items', [
            'source_type' => BankTransaction::class,
            'source_event_key' => 'unreconciled',
            'gross_amount_pence' => 1200,
        ]);
    }

    public function test_bank_reconciliation_changes_the_remaining_source_without_duplicates(): void
    {
        $invoice = $this->issuedInvoice();
        $payment = app(RecordInvoicePaymentAction::class)->execute($invoice, $this->owner, [
            'amount' => 40,
            'method' => 'bank_transfer',
            'bank_account_id' => $this->bankAccount->id,
            'reference' => 'UC-BANK-RECEIPT',
            'paid_at' => '2026-11-25',
        ]);
        $transaction = BankTransaction::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'bank_account_id' => $this->bankAccount->id,
            'provider_id' => 'UC-BANK-100',
            'booking_date' => '2026-11-25',
            'amount' => '100.00',
            'currency' => 'GBP',
            'reference' => 'Combined receipts',
            'source_hash' => hash('sha256', 'UC-BANK-100'),
            'reconciliation_status' => BankTransaction::STATUS_UNMATCHED,
        ]);
        $collector = app(CollectUniversalCreditSourcesAction::class);
        $collector->execute($this->ucPeriod, $this->owner);
        $bankItem = UniversalCreditReportItem::withoutGlobalScopes()
            ->where('source_type', BankTransaction::class)
            ->where('source_id', $transaction->id)
            ->firstOrFail();
        app(ClassifyUniversalCreditReportItemAction::class)->execute(
            $bankItem,
            $this->owner,
            UniversalCreditReportItem::STATUS_INCLUDED,
            'goods_and_services',
        );
        $reconciliation = BankReconciliation::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'bank_transaction_id' => $transaction->id,
            'created_by' => $this->owner->id,
            'status' => BankReconciliation::STATUS_ACTIVE,
            'total_amount' => '40.00',
            'currency' => 'GBP',
            'reconciled_at' => now(),
        ]);
        BankReconciliationAllocation::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'bank_reconciliation_id' => $reconciliation->id,
            'bank_transaction_id' => $transaction->id,
            'reconcilable_type' => Payment::class,
            'reconcilable_id' => $payment->id,
            'event_type' => 'receipt',
            'candidate_key' => "payment:{$payment->id}:receipt",
            'amount' => '40.00',
            'currency' => 'GBP',
            'source_amount' => '40.00',
            'source_currency' => 'GBP',
            'match_score' => 100,
        ]);

        $result = $collector->execute($this->ucPeriod, $this->owner);
        $bankItem->refresh();

        $this->assertSame(1, $result->updated);
        $this->assertSame(6000, $bankItem->gross_amount_pence);
        $this->assertSame('60.00', $bankItem->original_amount);
        $this->assertSame(UniversalCreditReportItem::STATUS_REVIEW_REQUIRED, $bankItem->classification_status);
        $this->assertNull($bankItem->reviewed_at);
        $this->assertSame('source_snapshot_changed', $bankItem->source_change_reason);
        $this->assertSame(2, $this->ucPeriod->reportItems()->count());
    }

    public function test_classification_requires_valid_categories_and_mixed_use_reason(): void
    {
        $expense = $this->postedExpense();
        app(RecordExpensePaymentAction::class)->execute($expense, $this->owner, [
            'amount' => 30,
            'method' => 'card',
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'reference' => 'UC-CLASSIFY-1',
            'paid_at' => '2026-12-02',
        ]);
        app(CollectUniversalCreditSourcesAction::class)->execute($this->ucPeriod, $this->owner);
        $item = UniversalCreditReportItem::withoutGlobalScopes()->firstOrFail();
        $action = app(ClassifyUniversalCreditReportItemAction::class);

        try {
            $action->execute(
                $item,
                $this->owner,
                UniversalCreditReportItem::STATUS_INCLUDED,
                'subscriptions_and_training',
                50,
            );
            $this->fail('A mixed-use expense must require an explanation.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.universal_credit.errors.business_use_reason_required'), $exception->getMessage());
        }

        $classified = $action->execute(
            $item,
            $this->owner,
            UniversalCreditReportItem::STATUS_INCLUDED,
            'subscriptions_and_training',
            '50.00',
            'Half of the subscription is used privately.',
            'Reviewed against the supplier invoice.',
        );

        $this->assertSame(5000, $classified->business_use_basis_points);
        $this->assertSame(UniversalCreditReportItem::METHOD_BUSINESS_USE, $classified->calculation_method);
        $this->assertSame($this->owner->id, $classified->reviewed_by);
        $this->assertNotNull($classified->reviewed_at);
        $this->assertNull($classified->reportable_amount_pence);
    }

    public function test_non_recoverable_purchase_vat_remains_in_the_cash_cost(): void
    {
        $blockedTaxCode = TaxCode::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'code' => 'BLOCKED20',
            'label' => ['en' => 'Blocked input VAT 20%', 'pl' => 'Nieodzyskiwalny VAT 20%', 'pt' => 'IVA não recuperável 20%'],
            'type' => 'standard',
            'rate' => 20,
            'input_eligible' => false,
            'output_eligible' => false,
            'vat_return_mapping' => [],
            'valid_from' => '2026-01-01',
            'is_active' => true,
            'is_system' => false,
        ]);
        $expense = $this->postedExpense($blockedTaxCode);
        $payment = app(RecordExpensePaymentAction::class)->execute($expense, $this->owner, [
            'amount' => 30,
            'method' => 'card',
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'reference' => 'UC-BLOCKED-VAT-1',
            'paid_at' => '2026-12-02',
        ]);

        app(CollectUniversalCreditSourcesAction::class)->execute($this->ucPeriod, $this->owner);

        $this->assertDatabaseHas('universal_credit_report_items', [
            'source_type' => ExpensePayment::class,
            'source_id' => $payment->id,
            'gross_amount_pence' => 3000,
            'vat_amount_pence' => 0,
            'vat_snapshot_source' => 'document',
        ]);
    }

    public function test_paid_fixed_asset_cost_is_flagged_from_the_cash_flow_without_a_duplicate_asset_event(): void
    {
        $expense = $this->postedExpense();
        $category = FixedAssetCategory::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'code' => 'UC-EQUIPMENT',
            'name' => ['en' => 'Equipment', 'pl' => 'Wyposażenie', 'pt' => 'Equipamento'],
            'asset_account_id' => $this->ledgerAccount('1500')->id,
            'accumulated_depreciation_account_id' => $this->ledgerAccount('1600')->id,
            'depreciation_expense_account_id' => $this->ledgerAccount('7000')->id,
            'default_depreciation_method' => FixedAssetCategory::METHOD_STRAIGHT_LINE,
            'default_useful_life_months' => 36,
            'default_residual_value_percent' => 0,
            'is_active' => true,
        ]);
        $asset = FixedAsset::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'fixed_asset_category_id' => $category->id,
            'number' => 'FA-UC-001',
            'name' => 'UC test equipment',
            'status' => FixedAsset::STATUS_DRAFT,
            'depreciation_method' => FixedAssetCategory::METHOD_STRAIGHT_LINE,
            'useful_life_months' => 36,
            'residual_value_percent' => 0,
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);
        $assetTransaction = FixedAssetTransaction::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'fixed_asset_id' => $asset->id,
            'type' => FixedAssetTransaction::TYPE_ACQUISITION,
            'status' => FixedAssetTransaction::STATUS_PREPARED,
            'event_date' => '2026-11-24',
            'expense_item_id' => $expense->items->first()->id,
            'amount_gbp' => '120.00',
            'created_by' => $this->owner->id,
        ]);
        $assetTransaction->update(['status' => FixedAssetTransaction::STATUS_POSTED]);
        $payment = app(RecordExpensePaymentAction::class)->execute($expense, $this->owner, [
            'amount' => 30,
            'method' => 'card',
            'settlement_source' => ExpensePayment::SOURCE_OWNER_FUNDS,
            'reference' => 'UC-ASSET-CASH-1',
            'paid_at' => '2026-12-02',
        ]);

        app(CollectUniversalCreditSourcesAction::class)->execute($this->ucPeriod, $this->owner);
        $item = UniversalCreditReportItem::withoutGlobalScopes()
            ->where('source_type', ExpensePayment::class)
            ->where('source_id', $payment->id)
            ->firstOrFail();

        $this->assertSame('excluded_or_disallowed', $item->category);
        $this->assertSame([$assetTransaction->id], $item->source_context['fixed_asset_transaction_ids']);
        $this->assertSame(1, $this->ucPeriod->reportItems()->count());
    }

    public function test_manual_entries_require_evidence_or_reason_and_can_be_voided(): void
    {
        $action = app(CreateUniversalCreditManualEntryAction::class);
        $data = [
            'direction' => UniversalCreditReportItem::DIRECTION_INCOME,
            'event_type' => UniversalCreditManualEntry::EVENT_TIPS,
            'category' => 'tips_and_gratuities',
            'cash_date' => '2026-12-05',
            'original_amount' => '25.50',
            'original_currency' => 'GBP',
            'description' => 'Cash tips received from customers.',
        ];

        try {
            $action->execute($this->ucPeriod, $this->owner, $data);
            $this->fail('Evidence or a missing-evidence reason must be required.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.universal_credit.errors.manual_evidence_required'), $exception->getMessage());
        }

        $entry = $action->execute($this->ucPeriod, $this->owner, [
            ...$data,
            'evidence_missing_reason' => 'The cash till did not produce a separate receipt.',
        ]);
        $item = $entry->reportItems->firstOrFail();
        $this->assertSame(2550, $entry->amount_gbp_pence);
        $this->assertSame(2550, $item->gross_amount_pence);
        $this->assertSame(UniversalCreditReportItem::STATUS_REVIEW_REQUIRED, $item->classification_status);

        app(ClassifyUniversalCreditReportItemAction::class)->execute(
            $item,
            $this->owner,
            UniversalCreditReportItem::STATUS_INCLUDED,
            'tips_and_gratuities',
        );
        $voided = app(VoidUniversalCreditManualEntryAction::class)->execute(
            $entry,
            $this->owner,
            'Duplicate cash entry recorded in error.',
        );
        $this->assertSame(UniversalCreditManualEntry::STATUS_VOID, $voided->status);
        $this->assertFalse($voided->reportItems->first()->source_is_current);
        $this->assertSame(UniversalCreditReportItem::STATUS_EXCLUDED, $voided->reportItems->first()->classification_status);

        $withEvidence = $action->execute(
            $this->ucPeriod,
            $this->owner,
            [
                'direction' => UniversalCreditReportItem::DIRECTION_EXPENSE,
                'event_type' => UniversalCreditManualEntry::EVENT_CASH_PAYMENT,
                'category' => 'other_business_expense',
                'cash_date' => '2026-12-06',
                'original_amount' => '10.00',
                'original_currency' => 'GBP',
                'description' => 'Small cash purchase with receipt.',
            ],
            UploadedFile::fake()->create('cash-receipt.pdf', 10, 'application/pdf'),
        );
        $this->assertCount(1, $withEvidence->attachments);
        $this->assertNull($withEvidence->evidence_missing_reason);
        Storage::disk('financial')->assertExists($withEvidence->attachments->first()->path);
    }

    public function test_sources_are_tenant_protected_and_auditable(): void
    {
        $entry = app(CreateUniversalCreditManualEntryAction::class)->execute($this->ucPeriod, $this->owner, [
            'direction' => UniversalCreditReportItem::DIRECTION_INCOME,
            'event_type' => UniversalCreditManualEntry::EVENT_GRANT,
            'category' => 'taxable_grant_or_subsidy',
            'cash_date' => '2026-12-05',
            'original_amount' => '100.00',
            'original_currency' => 'GBP',
            'description' => 'Test taxable business grant.',
            'evidence_missing_reason' => 'Evidence will be attached before review.',
        ]);
        $outsider = User::factory()->create(['is_active' => true]);
        $outsider->givePermissionTo(['view_universal_credit_reporting', 'manage_universal_credit_reporting']);
        $this->actingAs($outsider);

        $this->assertFalse(Gate::forUser($outsider)->allows('view', $entry));

        try {
            app(VoidUniversalCreditManualEntryAction::class)->execute($entry, $outsider, 'Unauthorised tenant action.');
            $this->fail('Another tenant must not change a manual entry.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.universal_credit.errors.period_manage_denied'), $exception->getMessage());
        }

        $this->artisan('finance:audit-tenants')->assertSuccessful();
    }

    private function issuedInvoice(): Invoice
    {
        $invoice = Invoice::create([
            'business_id' => $this->business->id,
            'number' => 'INV-UC-'.uniqid(),
            'client_id' => $this->client->id,
            'created_by' => $this->owner->id,
            'status' => 'draft',
            'currency' => 'GBP',
            'issue_date' => '2026-11-24',
            'tax_point' => '2026-11-24',
            'due_date' => '2026-12-20',
        ]);
        InvoiceItem::create([
            'business_id' => $this->business->id,
            'invoice_id' => $invoice->id,
            'tax_code_id' => $this->taxCode('S20')->id,
            'description' => 'UC test service',
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        return app(IssueInvoiceAction::class)->execute($invoice, $this->owner);
    }

    private function postedExpense(?TaxCode $taxCode = null): Expense
    {
        $expense = app(CreateExpenseAction::class)->execute($this->business, $this->owner, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'BILL-UC-'.uniqid(),
            'issue_date' => '2026-11-24',
            'tax_point' => '2026-11-24',
            'business_purpose' => 'UC source test',
            'items' => [[
                'description' => 'Business software',
                'category' => 'software',
                'quantity' => 1,
                'unit_price' => 100,
                'tax_code_id' => ($taxCode ?? $this->taxCode('S20'))->id,
            ]],
        ]);
        $expense = app(ApproveExpenseAction::class)->execute($expense, $this->owner);

        return app(PostExpenseAction::class)->execute($expense, $this->owner);
    }

    private function taxCode(string $code): TaxCode
    {
        return TaxCode::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function ledgerAccount(string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }
}
