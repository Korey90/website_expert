<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\IntegrationSettingsPage;
use App\Filament\Pages\PipelinePage;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\Briefing;
use App\Models\Business;
use App\Models\SalesOffer;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FilamentPermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Cache::flush();
    }

    public function test_manager_gets_permission_based_filament_access(): void
    {
        $manager = User::factory()->create(['is_active' => true]);
        $manager->assignRole('manager');

        $panel = Filament::getPanel('admin');

        $this->assertNotNull($panel);
        $this->assertTrue($manager->canAccessPanel($panel));

        $this->actingAs($manager);

        $this->assertTrue(ClientResource::canAccess());
        $this->assertTrue(PaymentResource::canCreate());
        $this->assertTrue(IntegrationSettingsPage::canAccess());
        $this->assertTrue(PipelinePage::canAccess());
        $this->assertFalse(RoleResource::canAccess());
        $this->assertFalse(PermissionResource::canAccess());
    }

    public function test_developer_has_read_only_filament_access(): void
    {
        $developer = User::factory()->create(['is_active' => true]);
        $developer->assignRole('developer');

        $panel = Filament::getPanel('admin');

        $this->assertNotNull($panel);
        $this->assertTrue($developer->canAccessPanel($panel));

        $this->actingAs($developer);

        $this->assertTrue(ClientResource::canAccess());
        $this->assertFalse(ClientResource::canCreate());
        $this->assertTrue(PaymentResource::canAccess());
        $this->assertFalse(PaymentResource::canCreate());
        $this->assertFalse(IntegrationSettingsPage::canAccess());
        $this->assertFalse(PipelinePage::canAccess());
        $this->assertFalse(RoleResource::canAccess());
        $this->assertFalse(PermissionResource::canAccess());
    }

    public function test_client_cannot_access_filament_panel(): void
    {
        $client = User::factory()->create(['is_active' => true]);
        $client->assignRole('client');

        $panel = Filament::getPanel('admin');

        $this->assertNotNull($panel);
        $this->assertFalse($client->canAccessPanel($panel));

        $this->actingAs($client);

        $this->assertFalse(ClientResource::canAccess());
        $this->assertFalse(IntegrationSettingsPage::canAccess());
    }

    public function test_finance_and_uk_tax_permissions_are_exposed_in_the_finance_role_group(): void
    {
        $groups = RoleResource::permissionGroups();

        $this->assertSame('Finance, Accounting & UK Tax', $groups['finance']['label']);
        $this->assertSame('Users & Panel Access', $groups['users']['label']);
        $this->assertTrue(RoleResource::permissionMatchesGroup('access_admin_panel', $groups['users']));

        foreach ([
            'view_finance',
            'manage_finance',
            'approve_finance',
            'post_finance',
            'close_finance_periods',
            'submit_tax_returns',
        ] as $permission) {
            $this->assertTrue(
                RoleResource::permissionMatchesGroup($permission, $groups['finance']),
                "Permission [{$permission}] is missing from the Finance role group."
            );
        }
    }

    public function test_admin_can_create_a_panel_user_with_business_membership(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $business = Business::query()->where('slug', 'website-expert')->firstOrFail();
        $developerRole = Role::findByName('developer');

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'Finance User',
                'email' => 'finance-user@example.test',
                'password' => 'valid-password-123',
                'is_active' => true,
                'roles' => [$developerRole->getKey()],
                'businesses' => [$business->getKey()],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'finance-user@example.test')->firstOrFail();

        $this->assertTrue($user->hasRole('developer'));
        $this->assertTrue($user->businessMemberships()
            ->where('business_id', $business->getKey())
            ->where('is_active', true)
            ->exists());
        $this->assertSame($business->getKey(), $user->currentBusiness()?->getKey());
    }

    public function test_briefing_and_sales_offer_policies_use_permissions(): void
    {
        $manager = User::factory()->create(['is_active' => true]);
        $manager->assignRole('manager');

        $developer = User::factory()->create(['is_active' => true]);
        $developer->assignRole('developer');

        $this->assertTrue($manager->can('viewAny', Briefing::class));
        $this->assertTrue($manager->can('create', Briefing::class));
        $this->assertTrue($developer->can('viewAny', Briefing::class));
        $this->assertFalse($developer->can('create', Briefing::class));

        $this->assertTrue($manager->can('viewAny', SalesOffer::class));
        $this->assertTrue($manager->can('create', SalesOffer::class));
        $this->assertFalse($developer->can('viewAny', SalesOffer::class));
        $this->assertFalse($developer->can('create', SalesOffer::class));
    }
}
