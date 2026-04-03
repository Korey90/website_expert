<?php

namespace Tests\Feature\LandingPage;

use App\Models\Business;
use App\Models\LandingPage;
use App\Models\User;
use App\Services\LandingPage\LandingPageService;
use App\Services\LandingPage\LandingPageSlugService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPagePublicationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_generation_is_globally_unique_for_public_lp_routes(): void
    {
        $businessA = Business::create([
            'name' => 'Business A',
            'slug' => 'business-a',
            'is_active' => true,
        ]);

        $businessB = Business::create([
            'name' => 'Business B',
            'slug' => 'business-b',
            'is_active' => true,
        ]);

        LandingPage::create([
            'business_id' => $businessA->id,
            'title' => 'Shared Offer',
            'slug' => 'shared-offer',
            'status' => LandingPage::STATUS_DRAFT,
        ]);

        $slug = app(LandingPageSlugService::class)->generate('Shared Offer', $businessB);

        $this->assertSame('shared-offer-2', $slug);
    }

    public function test_landing_page_panel_routes_are_isolated_per_tenant(): void
    {
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        $businessA = Business::create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'is_active' => true,
        ]);

        $businessB = Business::create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'is_active' => true,
        ]);

        $pageA = LandingPage::create([
            'business_id' => $businessA->id,
            'title' => 'Tenant A Page',
            'slug' => 'tenant-a-page',
            'status' => LandingPage::STATUS_DRAFT,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');
        $user->businesses()->attach($businessB->id, ['role' => 'owner', 'is_active' => true, 'joined_at' => now()]);

        $this->actingAs($user)
            ->get(route('landing-pages.edit', $pageA))
            ->assertNotFound();
    }

    public function test_publish_sets_status_and_published_at_for_public_runtime(): void
    {
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        $business = Business::create([
            'name' => 'Publishing Co',
            'slug' => 'publishing-co',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('admin');

        $page = LandingPage::create([
            'business_id' => $business->id,
            'title' => 'Ready To Publish',
            'slug' => 'ready-to-publish',
            'status' => LandingPage::STATUS_DRAFT,
        ]);

        $page->sections()->create([
            'type' => 'hero',
            'order' => 0,
            'content' => ['headline' => 'Hero'],
            'settings' => [],
            'is_visible' => true,
        ]);

        $page->sections()->create([
            'type' => 'form',
            'order' => 1,
            'content' => ['headline' => 'Form'],
            'settings' => [],
            'is_visible' => true,
        ]);

        $published = app(LandingPageService::class)->publish($page, $user);

        $this->assertSame(LandingPage::STATUS_PUBLISHED, $published->status);
        $this->assertNotNull($published->published_at);
    }
}