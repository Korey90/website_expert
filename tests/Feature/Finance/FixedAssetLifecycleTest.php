<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\AcquireFixedAssetAction;
use App\Actions\Finance\ApproveExpenseAction;
use App\Actions\Finance\CreateCreditNoteAction;
use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\CreateFixedAssetAction;
use App\Actions\Finance\CreateFixedAssetCategoryAction;
use App\Actions\Finance\CreatePurchaseCreditNoteAction;
use App\Actions\Finance\DisposeFixedAssetAction;
use App\Actions\Finance\ImproveFixedAssetAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\IssueInvoiceAction;
use App\Actions\Finance\PostExpenseAction;
use App\Actions\Finance\ReverseFixedAssetTransactionAction;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Expense;
use App\Models\FinancialAttachment;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\FixedAssetTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FixedAssetLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Supplier $supplier;

    private Client $client;

    private FixedAsset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Sole Trader Assets', 'slug' => 'sole-trader-assets', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        foreach (['manage_finance', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['manage_finance', 'post_finance']);
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
        $this->supplier = Supplier::factory()->for($this->business)->create();
        $this->client = Client::create([
            'business_id' => $this->business->id,
            'company_name' => 'Asset Buyer',
            'status' => 'active',
        ]);
        $category = app(CreateFixedAssetCategoryAction::class)->execute($this->business, $this->user, [
            'code' => 'equipment',
            'name' => ['en' => 'Equipment', 'pl' => 'Wyposażenie'],
            'asset_account_id' => $this->account('1500')->id,
            'accumulated_depreciation_account_id' => $this->account('1600')->id,
            'depreciation_expense_account_id' => $this->account('7000')->id,
            'default_depreciation_method' => FixedAssetCategory::METHOD_STRAIGHT_LINE,
            'default_useful_life_months' => 36,
            'default_residual_value_percent' => 0,
        ]);
        $this->asset = app(CreateFixedAssetAction::class)->execute($this->business, $this->user, [
            'fixed_asset_category_id' => $category->id,
            'name' => 'Workstation',
        ]);
    }

    public function test_full_lifecycle_posts_reclassification_disposal_and_exact_reversals(): void
    {
        $purchase = $this->postedExpense('Computer', 'software', 'S20', 100, '2026-11-01');
        $purchaseItem = $purchase->items()->firstOrFail();
        $asset = app(AcquireFixedAssetAction::class)->execute($this->asset, $purchaseItem, $this->user, '2026-11-01');

        $this->assertSame(FixedAsset::STATUS_ACTIVE, $asset->status);
        $this->assertSame('100.00', $asset->acquisition_cost_gbp);
        $acquisition = $asset->transactions->firstWhere('type', FixedAssetTransaction::TYPE_ACQUISITION);
        $this->assertNotNull($acquisition);
        $this->assertJournalAmounts($acquisition->journalEntry, ['1500' => ['100.00', '0.00'], '6200' => ['0.00', '100.00']]);
        $purchaseJournal = $purchase->journalEntries()->with('lines.ledgerAccount')->firstOrFail();
        $this->assertSame('20.00', $purchaseJournal->lines->firstWhere('ledgerAccount.code', '1300')->debit_gbp);
        $this->assertSame('120.00', $purchaseJournal->lines->firstWhere('ledgerAccount.code', '2100')->credit_gbp);

        app(AcquireFixedAssetAction::class)->execute($asset, $purchaseItem, $this->user, '2026-11-01');
        $this->assertDatabaseCount('fixed_asset_transactions', 1);
        $this->assertDatabaseCount('journal_entries', 2);

        $improvementExpense = $this->postedExpense('Memory upgrade', 'office', 'Z0', 25, '2026-11-05');
        $asset = app(ImproveFixedAssetAction::class)->execute(
            $asset,
            $improvementExpense->items()->firstOrFail(),
            $this->user,
            '2026-11-05',
        );
        $this->assertSame('125.00', $asset->acquisition_cost_gbp);
        $improvement = $asset->transactions->firstWhere('type', FixedAssetTransaction::TYPE_IMPROVEMENT);
        $this->assertJournalAmounts($improvement->journalEntry, ['1500' => ['25.00', '0.00'], '6300' => ['0.00', '25.00']]);

        [$invoice, $invoiceItem] = $this->issuedInvoice(150, '2026-11-10');
        $asset = app(DisposeFixedAssetAction::class)->execute($asset, $invoiceItem, $this->user, '2026-11-10');
        $this->assertSame(FixedAsset::STATUS_DISPOSED, $asset->status);
        $this->assertSame('150.00', $asset->disposal_proceeds_gbp);
        $this->assertSame('25.00', $asset->disposal_gain_loss_gbp);
        $disposal = $asset->transactions->firstWhere('type', FixedAssetTransaction::TYPE_DISPOSAL);
        $this->assertJournalAmounts($disposal->journalEntry, [
            '4100' => ['150.00', '0.00'],
            '1500' => ['0.00', '125.00'],
            '4200' => ['0.00', '25.00'],
        ]);
        $salesJournal = $invoice->journalEntries()->with('lines.ledgerAccount')->firstOrFail();
        $this->assertSame('180.00', $salesJournal->lines->firstWhere('ledgerAccount.code', '1200')->debit_gbp);
        $this->assertSame('30.00', $salesJournal->lines->firstWhere('ledgerAccount.code', '2200')->credit_gbp);

        $asset = app(ReverseFixedAssetTransactionAction::class)->execute($disposal, $this->user, 'Sale cancelled', '2026-11-10');
        $this->assertSame(FixedAsset::STATUS_ACTIVE, $asset->status);
        $this->assertNull($asset->disposed_at);
        $this->assertSame(JournalEntry::STATUS_REVERSED, $disposal->journalEntry->fresh()->status);

        $asset = app(ReverseFixedAssetTransactionAction::class)->execute($improvement, $this->user, 'Upgrade returned', '2026-11-10');
        $this->assertSame('100.00', $asset->acquisition_cost_gbp);
        $asset = app(ReverseFixedAssetTransactionAction::class)->execute($acquisition, $this->user, 'Purchase cancelled', '2026-11-10');
        $this->assertSame(FixedAsset::STATUS_DRAFT, $asset->status);
        $this->assertNull($asset->acquisition_cost_gbp);
    }

    public function test_clean_evidence_permission_and_tenant_guards_fail_closed(): void
    {
        $purchase = $this->postedExpense('Computer', 'software', 'S20', 100, '2026-11-01', false);

        try {
            app(AcquireFixedAssetAction::class)->execute($this->asset, $purchase->items()->firstOrFail(), $this->user, '2026-11-01');
            $this->fail('An acquisition without clean evidence should fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('clean scanned', $exception->getMessage());
        }

        $this->assertSame(FixedAsset::STATUS_DRAFT, $this->asset->fresh()->status);
        $this->assertDatabaseCount('fixed_asset_transactions', 0);

        $this->addEvidence($purchase);
        $this->user->revokePermissionTo('post_finance');
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('cannot post fixed asset');
        app(AcquireFixedAssetAction::class)->execute($this->asset, $purchase->items()->firstOrFail(), $this->user, '2026-11-01');
    }

    public function test_credit_notes_are_blocked_until_the_related_asset_transaction_is_reversed(): void
    {
        $purchase = $this->postedExpense('Computer', 'software', 'S20', 100, '2026-11-01');
        $asset = app(AcquireFixedAssetAction::class)->execute($this->asset, $purchase->items()->firstOrFail(), $this->user, '2026-11-01');

        try {
            app(CreatePurchaseCreditNoteAction::class)->execute($purchase, $this->user, [
                'reason' => 'Return',
                'issue_date' => '2026-11-02',
                'items' => [['expense_item_id' => $purchase->items()->firstOrFail()->id, 'quantity' => 1]],
            ]);
            $this->fail('A capitalised purchase item should not be credited.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Reverse the fixed asset transaction', $exception->getMessage());
        }

        [$invoice, $invoiceItem] = $this->issuedInvoice(150, '2026-11-10');
        $asset = app(DisposeFixedAssetAction::class)->execute($asset, $invoiceItem, $this->user, '2026-11-10');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Reverse the fixed asset disposal');
        app(CreateCreditNoteAction::class)->execute($invoice, $this->user, [
            'reason' => 'Sale return',
            'issue_date' => '2026-11-11',
            'items' => [['invoice_item_id' => $invoiceItem->id, 'quantity' => 1]],
        ]);
    }

    public function test_capitalised_cost_matches_purchase_posting_for_blocked_vat_and_reverse_charge(): void
    {
        TaxCode::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'code' => 'BLK20',
            'label' => ['en' => 'Blocked input VAT 20%', 'pl' => 'Nieodliczalny VAT 20%', 'pt' => 'IVA não dedutível 20%'],
            'type' => 'standard',
            'rate' => 20,
            'input_eligible' => false,
            'output_eligible' => false,
            'vat_return_mapping' => [],
            'valid_from' => '2026-01-01',
            'is_active' => true,
        ]);

        $blocked = $this->postedExpense('Blocked VAT equipment', 'software', 'BLK20', 100, '2026-11-01');
        $blockedAsset = app(AcquireFixedAssetAction::class)->execute(
            $this->asset,
            $blocked->items()->firstOrFail(),
            $this->user,
            '2026-11-01',
        );
        $this->assertSame('120.00', $blockedAsset->acquisition_cost_gbp);
        $this->assertSame(
            '120.00',
            $blocked->journalEntries()->firstOrFail()->lines()->where('ledger_account_id', $this->account('6200')->id)->value('debit_gbp'),
        );

        $reverseChargeAsset = app(CreateFixedAssetAction::class)->execute($this->business, $this->user, [
            'fixed_asset_category_id' => $this->asset->fixed_asset_category_id,
            'name' => 'Reverse charge equipment',
        ]);
        $reverseCharge = $this->postedExpense('Reverse charge equipment', 'professional_fees', 'RC20', 100, '2026-11-02');
        $reverseChargeAsset = app(AcquireFixedAssetAction::class)->execute(
            $reverseChargeAsset,
            $reverseCharge->items()->firstOrFail(),
            $this->user,
            '2026-11-02',
        );

        $this->assertSame('100.00', $reverseChargeAsset->acquisition_cost_gbp);
        $purchaseJournal = $reverseCharge->journalEntries()->with('lines.ledgerAccount')->firstOrFail();
        $this->assertSame('100.00', $purchaseJournal->lines->firstWhere('ledgerAccount.code', '6500')->debit_gbp);
        $this->assertSame('20.00', $purchaseJournal->lines->firstWhere('ledgerAccount.code', '1300')->debit_gbp);
        $this->assertSame('20.00', $purchaseJournal->lines->firstWhere('ledgerAccount.code', '2200')->credit_gbp);
    }

    private function postedExpense(
        string $description,
        string $category,
        string $taxCode,
        float $price,
        string $date,
        bool $withEvidence = true,
    ): Expense {
        $expense = app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'ASSET-'.uniqid(),
            'issue_date' => $date,
            'tax_point' => $date,
            'business_purpose' => 'Fixed asset source',
            'items' => [[
                'description' => $description,
                'category' => $category,
                'quantity' => 1,
                'unit_price' => $price,
                'tax_code_id' => $this->taxCode($taxCode)->id,
            ]],
        ]);
        $expense = app(ApproveExpenseAction::class)->execute($expense, $this->user);
        $expense = app(PostExpenseAction::class)->execute($expense, $this->user);

        if ($withEvidence) {
            $this->addEvidence($expense);
        }

        return $expense->fresh('items');
    }

    private function addEvidence(Expense $expense): void
    {
        FinancialAttachment::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'attachable_type' => $expense->getMorphClass(),
            'attachable_id' => $expense->id,
            'uploaded_by' => $this->user->id,
            'document_type' => 'purchase_invoice',
            'disk' => 'local',
            'path' => 'tests/'.uniqid().'.pdf',
            'original_name' => 'invoice.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'sha256' => hash('sha256', uniqid('', true)),
            'scan_status' => FinancialAttachment::SCAN_CLEAN,
            'retention_until' => '2033-11-01',
        ]);
    }

    /** @return array{Invoice, InvoiceItem} */
    private function issuedInvoice(float $price, string $date): array
    {
        $invoice = Invoice::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => 'INV-ASSET-'.uniqid(),
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
            'currency' => 'GBP',
            'issue_date' => $date,
            'tax_point' => $date,
            'due_date' => '2026-12-01',
        ]);
        $item = InvoiceItem::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'invoice_id' => $invoice->id,
            'tax_code_id' => $this->taxCode('S20')->id,
            'description' => 'Workstation sale',
            'quantity' => 1,
            'unit_price' => $price,
        ]);

        return [app(IssueInvoiceAction::class)->execute($invoice, $this->user), $item->fresh()];
    }

    /** @param array<string, array{string, string}> $expected */
    private function assertJournalAmounts(JournalEntry $journal, array $expected): void
    {
        $journal->loadMissing('lines.ledgerAccount');

        foreach ($expected as $code => [$debit, $credit]) {
            $line = $journal->lines->firstWhere('ledgerAccount.code', $code);
            $this->assertNotNull($line, "Missing journal line for account {$code}.");
            $this->assertSame($debit, $line->debit_gbp);
            $this->assertSame($credit, $line->credit_gbp);
        }
    }

    private function account(string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()->where('business_id', $this->business->id)->where('code', $code)->firstOrFail();
    }

    private function taxCode(string $code): TaxCode
    {
        return TaxCode::withoutGlobalScopes()->where('business_id', $this->business->id)->where('code', $code)->firstOrFail();
    }
}
