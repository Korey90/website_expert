<?php

namespace Tests\Feature\Finance;

use App\Filament\Resources\SupplierResource;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SupplierResourceTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Buyer Ltd', 'slug' => 'buyer', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        foreach (['access_admin_panel', 'view_finance', 'manage_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance']);
    }

    public function test_supplier_factory_creates_a_tenant_owned_uk_supplier(): void
    {
        $supplier = Supplier::factory()->for($this->business)->create();

        $this->assertSame($this->business->id, $supplier->business_id);
        $this->assertSame('GB', $supplier->country);
        $this->assertSame('GBP', $supplier->default_currency);
        $this->assertSame(Supplier::STATUS_ACTIVE, $supplier->status);
    }

    public function test_supplier_queries_are_isolated_between_tenants(): void
    {
        $ownSupplier = Supplier::factory()->for($this->business)->create(['name' => 'Own Supplier']);
        $otherBusiness = Business::create(['name' => 'Other Ltd', 'slug' => 'other-supplier', 'is_active' => true]);
        Supplier::factory()->for($otherBusiness)->create(['name' => 'Private Supplier']);

        $this->actingAs($this->user);

        $this->assertSame(1, Supplier::count());
        $this->assertTrue(Supplier::first()->is($ownSupplier));
        $this->assertFalse(Supplier::query()->where('name', 'Private Supplier')->exists());
    }

    public function test_supplier_policy_requires_finance_permission_and_current_tenant(): void
    {
        $ownSupplier = Supplier::factory()->for($this->business)->create();
        $otherBusiness = Business::create(['name' => 'Other Ltd', 'slug' => 'other-policy', 'is_active' => true]);
        $otherSupplier = Supplier::factory()->for($otherBusiness)->create();

        $this->actingAs($this->user);

        $this->assertTrue(Gate::forUser($this->user)->allows('view', $ownSupplier));
        $this->assertTrue(Gate::forUser($this->user)->allows('update', $ownSupplier));
        $this->assertFalse(Gate::forUser($this->user)->allows('view', $otherSupplier));
        $this->assertFalse(Gate::forUser($this->user)->allows('update', $otherSupplier));
        $this->assertFalse(Gate::forUser($this->user)->allows('forceDelete', $ownSupplier));
    }

    public function test_authorized_user_can_open_supplier_resource_pages(): void
    {
        $supplier = Supplier::factory()->for($this->business)->create(['name' => 'Cloud Hosting Ltd']);

        $this->actingAs($this->user)
            ->get(SupplierResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Cloud Hosting Ltd');

        $this->get(SupplierResource::getUrl('view', ['record' => $supplier]))
            ->assertOk()
            ->assertSee('Cloud Hosting Ltd');

        $this->get(SupplierResource::getUrl('create'))->assertOk();
        $this->get(SupplierResource::getUrl('edit', ['record' => $supplier]))->assertOk();
    }

    public function test_supplier_can_be_archived_without_losing_its_record(): void
    {
        $supplier = Supplier::factory()->for($this->business)->create();

        $supplier->update(['status' => Supplier::STATUS_ARCHIVED]);
        $supplier->delete();

        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
        $this->assertSame(Supplier::STATUS_ARCHIVED, Supplier::withTrashed()->findOrFail($supplier->id)->status);
    }
}
