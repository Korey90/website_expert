<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\ApproveExpenseAction;
use App\Actions\Finance\CreateAccountingPeriodAction;
use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostExpenseAction;
use App\Actions\Finance\TransitionAccountingPeriodAction;
use App\Actions\Finance\UpdateAccountingPeriodAction;
use App\Filament\Resources\AccountingPeriodResource;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Expense;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountingPeriodTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Period Books Ltd',
            'slug' => 'period-books',
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

        foreach (['access_admin_panel', 'view_finance', 'manage_finance', 'close_finance_periods'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance', 'close_finance_periods']);
    }

    public function test_it_creates_and_updates_non_overlapping_open_periods(): void
    {
        $period = $this->createPeriod();
        $updated = app(UpdateAccountingPeriodAction::class)->execute($period, $this->user, [
            'name' => 'FY 2026 adjusted',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-30',
        ]);

        $this->assertSame(AccountingPeriod::STATUS_OPEN, $updated->status);
        $this->assertSame('FY 2026 adjusted', $updated->name);
        $this->assertSame($this->user->id, $updated->created_by);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('overlaps');
        app(CreateAccountingPeriodAction::class)->execute($this->business, $this->user, [
            'name' => 'Overlapping quarter',
            'financial_year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2027-03-31',
        ]);
    }

    public function test_close_reopen_and_lock_transitions_are_audited_and_lock_is_irreversible(): void
    {
        $action = app(TransitionAccountingPeriodAction::class);
        $period = $this->createPeriod();
        $period = $action->execute($period, $this->user, AccountingPeriod::STATUS_CLOSING);
        $period = $action->execute($period, $this->user, AccountingPeriod::STATUS_CLOSED, 'Year-end review complete.');
        $period = $action->execute($period, $this->user, AccountingPeriod::STATUS_OPEN, 'Auditor adjustment required.');
        $period = $action->execute($period, $this->user, AccountingPeriod::STATUS_CLOSING);
        $period = $action->execute($period, $this->user, AccountingPeriod::STATUS_CLOSED);
        $period = $action->execute($period, $this->user, AccountingPeriod::STATUS_LOCKED, 'Statutory accounts filed.');

        $this->assertSame(AccountingPeriod::STATUS_LOCKED, $period->status);
        $this->assertSame($this->user->id, $period->locked_by);
        $this->assertSame('Statutory accounts filed.', $period->lock_reason);
        $this->assertSame('Auditor adjustment required.', $period->reopen_reason);
        $this->assertCount(6, $period->transition_history);
        $this->assertSame('closed', $period->transition_history[5]['from']);
        $this->assertSame('locked', $period->transition_history[5]['to']);

        $this->expectException(DomainException::class);
        $action->execute($period, $this->user, AccountingPeriod::STATUS_OPEN, 'Too late.');
    }

    public function test_sensitive_transitions_require_permission_and_a_reason(): void
    {
        $period = $this->createPeriod();
        $unauthorized = User::factory()->create();
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $unauthorized->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        try {
            app(TransitionAccountingPeriodAction::class)->execute($period, $unauthorized, AccountingPeriod::STATUS_CLOSING);
            $this->fail('A user without close_finance_periods should not transition a period.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot close', $exception->getMessage());
        }

        $period = app(TransitionAccountingPeriodAction::class)->execute($period, $this->user, AccountingPeriod::STATUS_CLOSING);
        $period = app(TransitionAccountingPeriodAction::class)->execute($period, $this->user, AccountingPeriod::STATUS_CLOSED);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('reason is required');
        app(TransitionAccountingPeriodAction::class)->execute($period, $this->user, AccountingPeriod::STATUS_LOCKED);
    }

    public function test_expense_posting_is_allowed_while_closing_but_blocked_after_close(): void
    {
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $supplier = Supplier::factory()->for($this->business)->create();
        $taxCode = TaxCode::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', 'S20')
            ->firstOrFail();
        $period = $this->createPeriod();
        $period = app(TransitionAccountingPeriodAction::class)->execute($period, $this->user, AccountingPeriod::STATUS_CLOSING);
        $closingExpense = $this->approvedExpense($supplier, $taxCode, 'BILL-CLOSING');

        $posted = app(PostExpenseAction::class)->execute($closingExpense, $this->user);

        $this->assertSame(Expense::STATUS_POSTED, $posted->status);

        $closedExpense = $this->approvedExpense($supplier, $taxCode, 'BILL-CLOSED');
        app(TransitionAccountingPeriodAction::class)->execute($period, $this->user, AccountingPeriod::STATUS_CLOSED);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('is closed');
        app(PostExpenseAction::class)->execute($closedExpense, $this->user);
    }

    public function test_periods_are_tenant_isolated_and_authorized_pages_are_available(): void
    {
        $period = $this->createPeriod();
        $otherBusiness = Business::create(['name' => 'Other Period Ltd', 'slug' => 'other-period', 'is_active' => true]);
        $otherPeriod = AccountingPeriod::factory()->for($otherBusiness)->create([
            'name' => 'Other FY',
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ]);

        $this->actingAs($this->user);

        $this->assertSame(1, AccountingPeriod::count());
        $this->assertTrue(Gate::forUser($this->user)->allows('view', $period));
        $this->assertTrue(Gate::forUser($this->user)->allows('update', $period));
        $this->assertTrue(Gate::forUser($this->user)->allows('transition', $period));
        $this->assertFalse(Gate::forUser($this->user)->allows('view', $otherPeriod));

        $this->get(AccountingPeriodResource::getUrl('index'))->assertOk()->assertSee('FY 2026');
        $this->get(AccountingPeriodResource::getUrl('create'))->assertOk();
        $this->get(AccountingPeriodResource::getUrl('view', ['record' => $period]))->assertOk();
        $this->get(AccountingPeriodResource::getUrl('edit', ['record' => $period]))->assertOk();
    }

    private function createPeriod(): AccountingPeriod
    {
        return app(CreateAccountingPeriodAction::class)->execute($this->business, $this->user, [
            'name' => 'FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
    }

    private function approvedExpense(Supplier $supplier, TaxCode $taxCode, string $invoiceNumber): Expense
    {
        $expense = app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_id' => $supplier->id,
            'supplier_invoice_number' => $invoiceNumber,
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'business_purpose' => 'Period posting verification',
            'items' => [[
                'description' => 'Software subscription',
                'quantity' => 1,
                'unit_price' => 100,
                'tax_code_id' => $taxCode->id,
            ]],
        ]);

        return app(ApproveExpenseAction::class)->execute($expense, $this->user);
    }
}
