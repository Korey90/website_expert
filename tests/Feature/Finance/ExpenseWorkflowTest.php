<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\ApproveExpenseAction;
use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostExpenseAction;
use App\Actions\Finance\UpdateExpenseAction;
use App\Actions\Finance\VoidExpenseAction;
use App\Filament\Resources\ExpenseResource;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Expense;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExpenseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Books Ltd', 'slug' => 'expense-books', 'is_active' => true]);
        $this->user = User::factory()->create();
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $this->supplier = Supplier::factory()->for($this->business)->create([
            'name' => 'Office Supplier Ltd',
            'payment_terms_days' => 14,
        ]);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        Permission::findOrCreate('post_finance', 'web');
        $this->user->givePermissionTo('post_finance');
        AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => 'FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_it_creates_a_draft_expense_with_mixed_purchase_vat_snapshots(): void
    {
        $expense = $this->createExpense([
            $this->item('Office equipment', 'S20', 100),
            $this->item('Zero-rated books', 'Z0', 50),
        ]);

        $this->assertSame(Expense::STATUS_DRAFT, $expense->status);
        $this->assertSame('150.00', $expense->subtotal);
        $this->assertSame('20.00', $expense->vat_amount);
        $this->assertSame('170.00', $expense->total);
        $this->assertSame('170.00', $expense->amount_due);
        $this->assertSame('170.00', $expense->total_gbp);
        $this->assertSame('2026-11-15', $expense->due_date?->toDateString());
        $this->assertSame('20.00', $expense->items->first()->vat_rate_snapshot);
    }

    public function test_foreign_currency_expense_snapshots_gbp_values(): void
    {
        $expense = $this->createExpense([
            $this->item('Euro subscription', 'S20', 100),
        ], [
            'currency' => 'EUR',
            'gbp_exchange_rate' => 0.85,
            'exchange_rate_source' => 'manual',
            'exchange_rate_date' => '2026-11-01',
        ]);

        $this->assertSame('85.00', $expense->subtotal_gbp);
        $this->assertSame('17.00', $expense->vat_amount_gbp);
        $this->assertSame('102.00', $expense->total_gbp);
    }

    public function test_purchase_reverse_charge_records_notional_vat_without_increasing_supplier_balance(): void
    {
        $expense = $this->createExpense([
            $this->item('Reverse charge service', 'RC20', 100),
        ]);
        $item = $expense->items->first();

        $this->assertSame('20.00', $item->vat_rate_snapshot);
        $this->assertSame('20.00', $item->vat_amount);
        $this->assertSame('100.00', $item->gross_amount);
        $this->assertSame('20.00', $expense->vat_amount);
        $this->assertSame('100.00', $expense->total);
        $this->assertSame('100.00', $expense->amount_due);
    }

    public function test_expense_can_be_updated_approved_and_posted_but_not_edited_after_approval(): void
    {
        $expense = $this->createExpense([$this->item('Initial item', 'S20', 100)]);
        $updated = app(UpdateExpenseAction::class)->execute($expense, $this->user, [
            'business_purpose' => 'Updated operational purpose',
            'items' => [$this->item('Updated item', 'R5', 200)],
        ]);
        $approved = app(ApproveExpenseAction::class)->execute($updated, $this->user);
        $posted = app(PostExpenseAction::class)->execute($approved, $this->user);

        $this->assertSame('200.00', $updated->subtotal);
        $this->assertSame('10.00', $updated->vat_amount);
        $this->assertSame(Expense::STATUS_APPROVED, $approved->status);
        $this->assertSame($this->user->id, $approved->approved_by);
        $this->assertNotNull($approved->approved_at);
        $this->assertSame(Expense::STATUS_POSTED, $posted->status);
        $this->assertSame($this->user->id, $posted->posted_by);

        $this->expectException(DomainException::class);
        app(UpdateExpenseAction::class)->execute($posted, $this->user, ['notes' => 'Forbidden edit']);
    }

    public function test_draft_or_approved_expense_can_be_voided_with_an_audit_reason(): void
    {
        $expense = $this->createExpense([$this->item('Cancelled order', 'S20', 50)]);
        $voided = app(VoidExpenseAction::class)->execute($expense, $this->user, 'Supplier cancelled the order.');

        $this->assertSame(Expense::STATUS_VOID, $voided->status);
        $this->assertSame($this->user->id, $voided->voided_by);
        $this->assertSame('Supplier cancelled the order.', $voided->void_reason);
        $this->assertNotNull($voided->voided_at);
        $this->assertDatabaseHas('expenses', ['id' => $voided->id, 'deleted_at' => null]);
    }

    public function test_posted_expense_cannot_be_voided_before_the_ledger_reversal_workflow_exists(): void
    {
        $expense = app(ApproveExpenseAction::class)->execute(
            $this->createExpense([$this->item('Posted purchase', 'S20', 50)]),
            $this->user,
        );
        $expense = app(PostExpenseAction::class)->execute($expense, $this->user);

        $this->expectException(DomainException::class);
        app(VoidExpenseAction::class)->execute($expense, $this->user, 'Correction required.');
    }

    public function test_cross_tenant_supplier_and_item_references_are_rejected(): void
    {
        $otherBusiness = Business::create(['name' => 'Other Ltd', 'slug' => 'other-expense', 'is_active' => true]);
        $otherSupplier = Supplier::factory()->for($otherBusiness)->create();
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness);
        $otherTaxCode = TaxCode::withoutGlobalScopes()
            ->where('business_id', $otherBusiness->id)
            ->where('code', 'S20')
            ->firstOrFail();

        try {
            $this->createExpense([$this->item('Own item', 'S20', 10)], ['supplier_id' => $otherSupplier->id]);
            $this->fail('A cross-tenant supplier should be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('supplier', strtolower($exception->getMessage()));
        }

        $this->expectException(DomainException::class);
        $this->createExpense([[
            'description' => 'Foreign tax code',
            'quantity' => 1,
            'unit_price' => 10,
            'tax_code_id' => $otherTaxCode->id,
        ]], ['supplier_invoice_number' => 'BILL-002']);
    }

    public function test_supplier_invoice_number_is_unique_per_tenant_and_supplier(): void
    {
        $this->createExpense([$this->item('First bill', 'S20', 10)]);

        $this->expectException(QueryException::class);
        $this->createExpense([$this->item('Duplicate bill', 'S20', 10)]);
    }

    public function test_expenses_and_items_are_isolated_between_tenants(): void
    {
        $ownExpense = $this->createExpense([$this->item('Own cost', 'S20', 10)]);
        $otherBusiness = Business::create(['name' => 'Other Tenant', 'slug' => 'other-tenant-expense', 'is_active' => true]);
        $otherUser = User::factory()->create();
        BusinessUser::create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherUser->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $otherSupplier = Supplier::factory()->for($otherBusiness)->create();
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness, $otherUser);
        app(CreateExpenseAction::class)->execute($otherBusiness, $otherUser, [
            'supplier_id' => $otherSupplier->id,
            'supplier_invoice_number' => 'PRIVATE-001',
            'issue_date' => '2026-11-01',
            'business_purpose' => 'Private',
            'items' => [[
                'description' => 'Private cost',
                'quantity' => 1,
                'unit_price' => 10,
                'tax_code_id' => $this->taxCodeFor($otherBusiness, 'S20')->id,
            ]],
        ]);

        $this->actingAs($this->user);

        $this->assertSame(1, Expense::count());
        $this->assertTrue(Expense::first()->is($ownExpense));
        $this->assertSame(1, $ownExpense->items()->count());
    }

    public function test_authorized_user_can_open_expense_resource_pages(): void
    {
        $this->user->update(['is_active' => true]);
        foreach (['access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance', 'post_finance']);
        $expense = $this->createExpense([$this->item('Panel expense', 'S20', 100)]);

        $this->actingAs($this->user)
            ->get(ExpenseResource::getUrl('index'))
            ->assertOk()
            ->assertSee('BILL-001');

        $this->get(ExpenseResource::getUrl('create'))->assertOk();
        $this->get(ExpenseResource::getUrl('view', ['record' => $expense]))
            ->assertOk()
            ->assertSee('Panel expense');
        $this->get(ExpenseResource::getUrl('edit', ['record' => $expense]))->assertOk();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, mixed>  $overrides
     */
    private function createExpense(array $items, array $overrides = []): Expense
    {
        return app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'BILL-001',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'business_purpose' => 'Ordinary business operations',
            'items' => $items,
            ...$overrides,
        ]);
    }

    /** @return array<string, mixed> */
    private function item(string $description, string $taxCode, float $unitPrice): array
    {
        return [
            'description' => $description,
            'quantity' => 1,
            'unit_price' => $unitPrice,
            'tax_code_id' => $this->taxCodeFor($this->business, $taxCode)->id,
        ];
    }

    private function taxCodeFor(Business $business, string $code): TaxCode
    {
        return TaxCode::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('code', $code)
            ->firstOrFail();
    }
}
