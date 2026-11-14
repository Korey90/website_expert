<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ClientResource;
use App\Filament\Support\AdminNavigation;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
    }

    public function test_admin_navigation_has_seven_bounded_task_groups(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $groups = AdminNavigation::groups();

        $this->assertCount(7, $groups);
        $this->assertSame([
            __('admin.navigation.groups.start'),
            __('admin.navigation.groups.sales'),
            __('admin.navigation.groups.delivery'),
            __('admin.navigation.groups.finance'),
            __('admin.navigation.groups.marketing'),
            __('admin.navigation.groups.automation'),
            __('admin.navigation.groups.administration'),
        ], collect($groups)->map->getLabel()->all());

        foreach ($groups as $group) {
            $items = collect($group->getItems());

            $this->assertNotEmpty($items, "Navigation group [{$group->getLabel()}] is empty.");
            $this->assertLessThanOrEqual(
                6,
                $items->count(),
                "Navigation group [{$group->getLabel()}] exceeds six direct items.",
            );
        }
    }

    public function test_panel_only_user_sees_no_unauthorized_navigation_destinations(): void
    {
        $user = $this->tenantUser('navigation-panel-only@example.test');
        $user->givePermissionTo('access_admin_panel');
        $this->actingAs($user);

        $groups = AdminNavigation::groups();

        $this->assertCount(1, $groups);
        $this->assertSame(__('admin.navigation.groups.start'), $groups[0]->getLabel());
        $this->assertCount(1, $groups[0]->getItems());
        $this->assertSame(__('admin.navigation.items.dashboard'), $groups[0]->getItems()[0]->getLabel());

        $destinations = AdminNavigation::searchableDestinations();

        $this->assertCount(1, $destinations);
        $this->assertSame(__('admin.navigation.items.dashboard'), $destinations[0]['label']);
    }

    public function test_searchable_navigation_contains_unique_authorized_urls(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $destinations = AdminNavigation::searchableDestinations();
        $urls = collect($destinations)->pluck('url');

        $this->assertNotEmpty($destinations);
        $this->assertCount($urls->unique()->count(), $urls);
        $this->assertContains(__('admin.navigation.items.clients'), collect($destinations)->pluck('label'));
        $this->assertContains(__('admin.navigation.items.invoices'), collect($destinations)->pluck('label'));
    }

    public function test_record_global_search_keeps_tenant_scope(): void
    {
        $manager = $this->tenantUser('global-search-manager@example.test');
        $manager->assignRole('manager');
        $currentBusiness = $manager->currentBusiness();
        $otherBusiness = Business::create([
            'name' => 'Other Search Tenant',
            'slug' => 'other-search-tenant',
            'is_active' => true,
        ]);

        Client::withoutGlobalScopes()->create([
            'business_id' => $currentBusiness->getKey(),
            'company_name' => 'Needle Current Tenant',
        ]);
        Client::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->getKey(),
            'company_name' => 'Needle Other Tenant',
        ]);

        $this->actingAs($manager);

        $results = ClientResource::getGlobalSearchResults('Needle');

        $this->assertCount(1, $results);
        $this->assertSame('Needle Current Tenant', $results->first()->title);
    }

    private function tenantUser(string $email): User
    {
        $business = Business::query()->where('slug', 'website-expert')->firstOrFail();
        $user = User::factory()->create(['email' => $email, 'is_active' => true]);

        BusinessUser::create([
            'business_id' => $business->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return $user;
    }
}
