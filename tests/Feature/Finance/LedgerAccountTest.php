<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Filament\Resources\LedgerAccountResource;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\LedgerAccount;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LedgerAccountTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Ledger Ltd', 'slug' => 'ledger', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);

        foreach (['access_admin_panel', 'view_finance', 'manage_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance']);
    }

    public function test_default_uk_chart_has_translated_hierarchy_and_accounting_attributes(): void
    {
        $software = $this->account('6200');

        $this->assertSame(35, LedgerAccount::withoutGlobalScopes()->where('business_id', $this->business->id)->count());
        $this->assertSame('expense', $software->type);
        $this->assertSame('software', $software->subtype);
        $this->assertSame('debit', $software->normal_balance);
        $this->assertTrue($software->is_postable);
        $this->assertTrue($software->is_system);
        $this->assertSame('5000', $software->parent->code);
        $this->assertSame('Oprogramowanie i subskrypcje', $software->getTranslation('name', 'pl'));
    }

    public function test_accounts_are_tenant_isolated_and_policy_protects_system_accounts(): void
    {
        $otherBusiness = Business::create(['name' => 'Other Ledger Ltd', 'slug' => 'other-ledger', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness);
        $ownAccount = $this->account('6200');
        $otherAccount = LedgerAccount::withoutGlobalScopes()->where('business_id', $otherBusiness->id)->where('code', '6200')->firstOrFail();
        $customAccount = LedgerAccount::factory()->for($this->business)->create(['code' => '7999']);

        $this->actingAs($this->user);

        $this->assertSame(36, LedgerAccount::count());
        $this->assertTrue(Gate::forUser($this->user)->allows('view', $ownAccount));
        $this->assertTrue(Gate::forUser($this->user)->allows('update', $ownAccount));
        $this->assertFalse(Gate::forUser($this->user)->allows('delete', $ownAccount));
        $this->assertTrue(Gate::forUser($this->user)->allows('delete', $customAccount));
        $this->assertFalse(Gate::forUser($this->user)->allows('view', $otherAccount));
    }

    public function test_expense_rejects_a_ledger_account_from_another_tenant(): void
    {
        $otherBusiness = Business::create(['name' => 'Foreign Ledger Ltd', 'slug' => 'foreign-ledger', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness);
        $foreignAccount = LedgerAccount::withoutGlobalScopes()->where('business_id', $otherBusiness->id)->where('code', '6200')->firstOrFail();
        $supplier = Supplier::factory()->for($this->business)->create();

        $this->expectException(DomainException::class);
        app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_id' => $supplier->id,
            'issue_date' => '2026-11-04',
            'business_purpose' => 'Tenant validation',
            'items' => [[
                'description' => 'Foreign account',
                'quantity' => 1,
                'unit_price' => 10,
                'tax_code_id' => TaxCode::withoutGlobalScopes()->where('business_id', $this->business->id)->where('code', 'S20')->value('id'),
                'ledger_account_id' => $foreignAccount->id,
            ]],
        ]);
    }

    public function test_expense_category_selects_the_default_uk_ledger_account(): void
    {
        $supplier = Supplier::factory()->for($this->business)->create();
        $expense = app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_id' => $supplier->id,
            'issue_date' => '2026-11-04',
            'business_purpose' => 'Automatic account mapping',
            'items' => [[
                'description' => 'Monthly software',
                'quantity' => 1,
                'unit_price' => 25,
                'category' => 'software',
                'tax_code_id' => TaxCode::withoutGlobalScopes()->where('business_id', $this->business->id)->where('code', 'S20')->value('id'),
            ]],
        ]);

        $this->assertSame('6200', $expense->items->first()->ledgerAccount->code);
    }

    public function test_authorized_user_can_open_chart_of_accounts_pages(): void
    {
        $account = $this->account('6200');

        $this->actingAs($this->user)
            ->get(LedgerAccountResource::getUrl('index'))
            ->assertOk()
            ->assertSee('1000');

        $this->get(LedgerAccountResource::getUrl('create'))->assertOk();
        $this->get(LedgerAccountResource::getUrl('edit', ['record' => $account]))->assertOk();
    }

    private function account(string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }
}
