<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\ApproveExpenseAction;
use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\CreatePurchaseCreditNoteAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\IssuePurchaseCreditNoteAction;
use App\Actions\Finance\PostExpenseAction;
use App\Actions\Finance\VoidPurchaseCreditNoteAction;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\PurchaseCreditNote;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PurchasePostingTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Supplier $supplier;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Purchase Ledger Ltd', 'slug' => 'purchase-ledger', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $this->supplier = Supplier::factory()->for($this->business)->create();
        Permission::findOrCreate('post_finance', 'web');
        $this->user->givePermissionTo('post_finance');
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

    public function test_posting_a_mixed_vat_expense_debits_costs_and_input_vat_and_credits_supplier(): void
    {
        $expense = $this->approvedExpense([
            $this->item('Software', 'software', 'S20', 100),
            $this->item('Books', 'office', 'Z0', 50),
        ]);

        $posted = app(PostExpenseAction::class)->execute($expense, $this->user);
        $journal = $posted->journalEntries()->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame(Expense::STATUS_POSTED, $posted->status);
        $this->assertSame(JournalEntry::STATUS_POSTED, $journal->status);
        $this->assertSame('100.00', $journal->lines->firstWhere('ledgerAccount.code', '6200')->debit_gbp);
        $this->assertSame('50.00', $journal->lines->firstWhere('ledgerAccount.code', '6300')->debit_gbp);
        $this->assertSame('20.00', $journal->lines->firstWhere('ledgerAccount.code', '1300')->debit_gbp);
        $this->assertSame('170.00', $journal->lines->firstWhere('ledgerAccount.code', '2100')->credit_gbp);
        $this->assertSame($this->supplier->id, $journal->lines->first()->supplier_id);

        $retried = app(PostExpenseAction::class)->execute($posted, $this->user);
        $this->assertTrue($retried->journalEntries()->firstOrFail()->is($journal));
        $this->assertDatabaseCount('journal_entries', 1);

        $this->expectException(DomainException::class);
        $posted->fresh()->items->first()->update(['description' => 'Tampered cost']);
    }

    public function test_reverse_charge_posts_equal_input_and_output_vat_without_increasing_creditor(): void
    {
        $posted = app(PostExpenseAction::class)->execute(
            $this->approvedExpense([$this->item('EU service', 'professional_fees', 'RC20', 100)]),
            $this->user,
        );
        $lines = $posted->journalEntries()->with('lines.ledgerAccount')->firstOrFail()->lines;

        $this->assertSame('100.00', $lines->firstWhere('ledgerAccount.code', '6500')->debit_gbp);
        $this->assertSame('20.00', $lines->firstWhere('ledgerAccount.code', '1300')->debit_gbp);
        $this->assertSame('20.00', $lines->firstWhere('ledgerAccount.code', '2200')->credit_gbp);
        $this->assertSame('100.00', $lines->firstWhere('ledgerAccount.code', '2100')->credit_gbp);
        $this->assertSame('120.00', number_format($lines->sum('debit_gbp'), 2, '.', ''));
        $this->assertSame('120.00', number_format($lines->sum('credit_gbp'), 2, '.', ''));
    }

    public function test_foreign_currency_purchase_preserves_original_amounts_and_gbp_posting(): void
    {
        $expense = $this->approvedExpense(
            [$this->item('Euro software', 'software', 'S20', 100)],
            [
                'currency' => 'EUR',
                'gbp_exchange_rate' => '0.85000000',
                'exchange_rate_source' => 'manual',
                'exchange_rate_date' => '2026-11-01',
            ],
        );

        $posted = app(PostExpenseAction::class)->execute($expense, $this->user);
        $creditor = $posted->journalEntries()->firstOrFail()->lines()->whereHas(
            'ledgerAccount',
            fn ($query) => $query->where('code', '2100'),
        )->firstOrFail();

        $this->assertSame('102.00', $creditor->credit_gbp);
        $this->assertSame('EUR', $creditor->original_currency);
        $this->assertSame('120.00', $creditor->original_amount);
        $this->assertSame('0.85000000', $creditor->exchange_rate);
    }

    public function test_issuing_purchase_credit_reverses_creditor_cost_and_input_vat(): void
    {
        $expense = app(PostExpenseAction::class)->execute(
            $this->approvedExpense([$this->item('Credited software', 'software', 'S20', 100)]),
            $this->user,
        );
        $credit = app(CreatePurchaseCreditNoteAction::class)->execute($expense, $this->user, [
            'reason' => 'Supplier price correction',
            'issue_date' => '2026-11-04',
            'tax_point' => '2026-11-04',
            'items' => [[
                'expense_item_id' => $expense->items->first()->id,
                'quantity' => 0.5,
            ]],
        ]);

        $issued = app(IssuePurchaseCreditNoteAction::class)->execute($credit, $this->user);
        $journal = $issued->journalEntries()->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame(PurchaseCreditNote::STATUS_ISSUED, $issued->status);
        $this->assertSame('60.00', $journal->lines->firstWhere('ledgerAccount.code', '2100')->debit_gbp);
        $this->assertSame('50.00', $journal->lines->firstWhere('ledgerAccount.code', '6200')->credit_gbp);
        $this->assertSame('10.00', $journal->lines->firstWhere('ledgerAccount.code', '1300')->credit_gbp);

        app(IssuePurchaseCreditNoteAction::class)->execute($issued, $this->user);
        $this->assertDatabaseCount('journal_entries', 2);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('must be reversed');
        app(VoidPurchaseCreditNoteAction::class)->execute($issued, $this->user);
    }

    public function test_closed_period_or_missing_permission_rolls_back_expense_status(): void
    {
        $expense = $this->approvedExpense([$this->item('Blocked software', 'software', 'S20', 100)]);
        $this->period->update(['status' => AccountingPeriod::STATUS_CLOSED]);

        try {
            app(PostExpenseAction::class)->execute($expense, $this->user);
            $this->fail('A closed period should reject purchase posting.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('is closed', $exception->getMessage());
        }

        $this->assertSame(Expense::STATUS_APPROVED, $expense->fresh()->status);
        $this->assertDatabaseCount('journal_entries', 0);

        $this->period->update(['status' => AccountingPeriod::STATUS_OPEN]);
        $this->user->revokePermissionTo('post_finance');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('cannot post');
        app(PostExpenseAction::class)->execute($expense, $this->user);
    }

    /** @param list<array<string, mixed>> $items @param array<string, mixed> $overrides */
    private function approvedExpense(array $items, array $overrides = []): Expense
    {
        $expense = app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'BILL-'.uniqid(),
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'business_purpose' => 'Purchase posting verification',
            'items' => $items,
            ...$overrides,
        ]);

        return app(ApproveExpenseAction::class)->execute($expense, $this->user);
    }

    /** @return array<string, mixed> */
    private function item(string $description, string $category, string $taxCode, float $price): array
    {
        return [
            'description' => $description,
            'category' => $category,
            'quantity' => 1,
            'unit_price' => $price,
            'tax_code_id' => TaxCode::withoutGlobalScopes()
                ->where('business_id', $this->business->id)
                ->where('code', $taxCode)
                ->value('id'),
        ];
    }
}
