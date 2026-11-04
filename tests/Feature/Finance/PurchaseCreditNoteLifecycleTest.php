<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\AllocatePurchaseCreditNoteAction;
use App\Actions\Finance\ApproveExpenseAction;
use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\CreatePurchaseCreditNoteAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\IssuePurchaseCreditNoteAction;
use App\Actions\Finance\RecordPurchaseCreditRefundAction;
use App\Actions\Finance\VoidPurchaseCreditNoteAction;
use App\Filament\Resources\PurchaseCreditNoteResource;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Expense;
use App\Models\PurchaseCreditNote;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PurchaseCreditNoteLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Expense $expense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Purchase Credits Ltd', 'slug' => 'purchase-credits', 'is_active' => true]);
        $this->user = User::factory()->create();
        $this->addMember($this->business, $this->user);
        $supplier = Supplier::factory()->for($this->business)->create();
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $this->expense = app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_id' => $supplier->id,
            'supplier_invoice_number' => 'BILL-PCN-001',
            'issue_date' => '2026-11-01',
            'currency' => 'GBP',
            'business_purpose' => 'Goods purchased for business operations.',
            'items' => [
                $this->item('Standard goods', 'S20', 100),
                $this->item('Zero-rated goods', 'Z0', 50),
            ],
        ]);
        $this->expense = app(ApproveExpenseAction::class)->execute($this->expense, $this->user);
    }

    public function test_it_creates_a_partial_purchase_credit_from_immutable_expense_snapshots(): void
    {
        $credit = $this->createCredit(0.5);
        $item = $credit->items->first();

        $this->assertSame(PurchaseCreditNote::STATUS_DRAFT, $credit->status);
        $this->assertSame('PCN-2026-001', $credit->number);
        $this->assertSame('50.00', $credit->subtotal);
        $this->assertSame('10.00', $credit->vat_amount);
        $this->assertSame('60.00', $credit->total);
        $this->assertSame('20.00', $item->vat_rate_snapshot);
        $this->assertSame('60.00', $item->gross_amount);
        $this->assertSame($this->expense->supplier_id, $credit->supplier_id);
    }

    public function test_total_credited_quantity_cannot_exceed_the_expense_item(): void
    {
        $this->createCredit(0.75);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('exceeds');

        $this->createCredit(0.5);
    }

    public function test_issued_credit_can_be_allocated_against_the_expense_balance(): void
    {
        $credit = app(IssuePurchaseCreditNoteAction::class)->execute($this->createCredit(0.5), $this->user);
        $credit = app(AllocatePurchaseCreditNoteAction::class)->execute($credit, $this->user, 25);

        $this->assertSame(PurchaseCreditNote::STATUS_PARTIALLY_SETTLED, $credit->status);
        $this->assertSame('25.00', $credit->allocated_amount);
        $this->assertSame('145.00', $credit->expense->amount_due);

        $credit = app(AllocatePurchaseCreditNoteAction::class)->execute($credit, $this->user, 35);

        $this->assertSame(PurchaseCreditNote::STATUS_SETTLED, $credit->status);
        $this->assertSame('60.00', $credit->allocated_amount);
        $this->assertSame('110.00', $credit->expense->amount_due);
    }

    public function test_supplier_refund_is_recorded_and_can_settle_the_remaining_credit(): void
    {
        $this->expense->update(['amount_paid' => 100, 'amount_due' => 70]);
        $credit = app(IssuePurchaseCreditNoteAction::class)->execute($this->createCredit(0.5), $this->user);
        $credit = app(AllocatePurchaseCreditNoteAction::class)->execute($credit, $this->user, 20);
        $refund = app(RecordPurchaseCreditRefundAction::class)->execute($credit, $this->user, [
            'amount' => 40,
            'method' => 'bank_transfer',
            'reference' => 'RF-1001',
            'received_at' => '2026-11-04',
        ]);
        $credit->refresh();

        $this->assertSame('40.00', $refund->amount);
        $this->assertSame('GBP', $refund->currency);
        $this->assertSame($this->expense->id, $refund->expense_id);
        $this->assertSame('40.00', $credit->refunded_amount);
        $this->assertSame(PurchaseCreditNote::STATUS_SETTLED, $credit->status);
        $this->assertDatabaseHas('purchase_credit_refunds', ['reference' => 'RF-1001']);
    }

    public function test_refund_cannot_exceed_cash_previously_paid_for_the_expense(): void
    {
        $this->expense->update(['amount_paid' => 10, 'amount_due' => 160]);
        $credit = app(IssuePurchaseCreditNoteAction::class)->execute($this->createCredit(0.5), $this->user);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('amount paid');

        app(RecordPurchaseCreditRefundAction::class)->execute($credit, $this->user, [
            'amount' => 20,
            'method' => 'card',
        ]);
    }

    public function test_void_credit_releases_the_quantity_for_a_replacement_note(): void
    {
        $credit = app(VoidPurchaseCreditNoteAction::class)->execute($this->createCredit(1), $this->user);

        $this->assertSame(PurchaseCreditNote::STATUS_VOID, $credit->status);
        $replacement = $this->createCredit(1);
        $this->assertSame('120.00', $replacement->total);
    }

    public function test_settled_purchase_credit_cannot_be_voided(): void
    {
        $credit = app(IssuePurchaseCreditNoteAction::class)->execute($this->createCredit(0.5), $this->user);
        $credit = app(AllocatePurchaseCreditNoteAction::class)->execute($credit, $this->user, 60);

        $this->expectException(DomainException::class);
        app(VoidPurchaseCreditNoteAction::class)->execute($credit, $this->user);
    }

    public function test_purchase_credit_actions_and_queries_are_tenant_isolated(): void
    {
        $credit = $this->createCredit(0.5);
        $otherBusiness = Business::create(['name' => 'Other Credits Ltd', 'slug' => 'other-credits', 'is_active' => true]);
        $otherUser = User::factory()->create();
        $this->addMember($otherBusiness, $otherUser);

        try {
            app(IssuePurchaseCreditNoteAction::class)->execute($credit, $otherUser);
            $this->fail('A cross-tenant actor should not issue the purchase credit note.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('does not belong', $exception->getMessage());
        }

        $this->actingAs($otherUser);
        $this->assertNull(PurchaseCreditNote::find($credit->id));
        $this->assertNotNull(PurchaseCreditNote::withoutGlobalScopes()->find($credit->id));
    }

    public function test_authorized_user_can_open_purchase_credit_resource_pages(): void
    {
        $this->user->update(['is_active' => true]);
        foreach (['access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance', 'post_finance']);
        $credit = $this->createCredit(0.5);

        $this->actingAs($this->user);
        $this->assertTrue($this->user->hasPermissionTo('view_finance'));
        $this->assertTrue(Gate::allows('viewAny', PurchaseCreditNote::class));
        $this->assertTrue(PurchaseCreditNoteResource::canViewAny());

        $this->get(PurchaseCreditNoteResource::getUrl('index'))->assertOk();
        $this->get(PurchaseCreditNoteResource::getUrl('view', ['record' => $credit]))
            ->assertOk();
    }

    private function createCredit(float $quantity): PurchaseCreditNote
    {
        return app(CreatePurchaseCreditNoteAction::class)->execute($this->expense, $this->user, [
            'reason' => 'Supplier returned part of the order.',
            'issue_date' => '2026-11-04',
            'items' => [[
                'expense_item_id' => $this->expense->items->first()->id,
                'quantity' => $quantity,
            ]],
        ]);
    }

    /** @return array<string, mixed> */
    private function item(string $description, string $taxCode, float $price): array
    {
        return [
            'description' => $description,
            'quantity' => 1,
            'unit_price' => $price,
            'tax_code_id' => TaxCode::withoutGlobalScopes()
                ->where('business_id', $this->business->id)
                ->where('code', $taxCode)
                ->value('id'),
        ];
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
