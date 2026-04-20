<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\IntegrationSettingsPage;
use App\Filament\Pages\PipelinePage;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\RoleResource;
use App\Models\Briefing;
use App\Models\SalesOffer;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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