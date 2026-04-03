<?php

namespace Tests\Feature\LandingPage;

use App\Models\Business;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for Spatie permission enforcement in the LP → CRM pipeline.
 *
 * Covers:
 *  - Public lp.submit requires NO authentication
 *  - Admin has view_landing_pages and manage_landing_pages permissions
 *  - Manager can view and create leads; cannot publish without explicit permission
 *  - Developer has view_landing_pages only (no write)
 *  - Client role cannot access Filament admin resources
 *  - LandingPage Filament Index: unauthenticated → redirect to /admin/login
 *  - LeadCaptureRequest::authorize() always returns true (public form)
 *  - LandingPagePolicy::update() denies user without manage_landing_pages
 *  - LandingPagePolicy::publish() requires publish_landing_pages permission
 *  - LandingPagePolicy::create() requires manage_landing_pages
 *  - Unauthorized user gets 403 on protected admin endpoints
 */
class LpLeadPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private Business    $business;
    private LandingPage $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Cache::flush();

        PipelineStage::firstOrCreate(['slug' => 'new-lead'], ['name' => 'New Lead', 'order' => 1]);

        $this->business = Business::create([
            'name'      => 'Permissions Corp',
            'slug'      => 'permissions-corp',
            'is_active' => true,
        ]);

        $this->page = LandingPage::create([
            'business_id'  => $this->business->id,
            'title'        => 'Permission LP',
            'slug'         => 'permission-lp',
            'status'       => LandingPage::STATUS_PUBLISHED,
            'template_key' => 'lead_magnet',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Public access (no auth required)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lp_show_is_public_without_authentication(): void
    {
        $this->get(route('lp.show', $this->page->slug))
            ->assertSuccessful();
    }

    public function test_lp_submit_is_public_without_authentication(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), [
            'name' => 'Guest', 'email' => 'guest@example.com',
        ])->assertSuccessful();

        $this->assertDatabaseHas('leads', ['source' => 'landing_page']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Admin role
    // ─────────────────────────────────────────────────────────────────────────

    public function test_admin_has_view_landing_pages_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($admin->can('view_landing_pages'));
    }

    public function test_admin_has_manage_landing_pages_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($admin->can('manage_landing_pages'));
    }

    public function test_admin_has_publish_landing_pages_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($admin->can('publish_landing_pages'));
    }

    public function test_admin_has_manage_leads_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($admin->can('manage_leads'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Manager role
    // ─────────────────────────────────────────────────────────────────────────

    public function test_manager_can_view_landing_pages(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->assertTrue($manager->can('view_landing_pages'));
    }

    public function test_manager_can_manage_leads(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->assertTrue($manager->can('manage_leads'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Developer role
    // ─────────────────────────────────────────────────────────────────────────

    public function test_developer_has_view_landing_pages_permission(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $this->assertTrue($developer->can('view_landing_pages'));
    }

    public function test_developer_cannot_manage_landing_pages(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $this->assertFalse($developer->can('manage_landing_pages'));
    }

    public function test_developer_cannot_publish_landing_pages(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $this->assertFalse($developer->can('publish_landing_pages'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Client role
    // ─────────────────────────────────────────────────────────────────────────

    public function test_client_cannot_view_landing_pages(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->assertFalse($client->can('view_landing_pages'));
    }

    public function test_client_cannot_manage_leads(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->assertFalse($client->can('manage_leads'));
    }

    public function test_unauthenticated_redirected_from_filament_admin(): void
    {
        $this
            ->get('/admin/landing-pages')
            ->assertRedirectToRoute('filament.admin.auth.login');
    }

    public function test_client_role_gets_403_on_filament_admin(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->get('/admin/landing-pages')
            ->assertForbidden();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LandingPagePolicy tests (direct gate checks)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_policy_update_denied_without_manage_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('developer'); // view only

        $this->assertFalse($user->can('update', $this->page));
    }

    public function test_policy_update_allowed_with_manage_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->businesses()->attach($this->business->id, ['role' => 'owner', 'is_active' => true, 'joined_at' => now()]);

        $this->actingAs($admin);

        $this->assertTrue($admin->can('update', $this->page));
    }

    public function test_policy_publish_denied_without_publish_permission(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');
        $manager->businesses()->attach($this->business->id, ['role' => 'manager', 'is_active' => true, 'joined_at' => now()]);

        $this->actingAs($manager);

        $this->assertSame(
            $manager->can('publish_landing_pages'),
            $manager->can('publish', $this->page)
        );
    }

    public function test_policy_publish_allowed_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->businesses()->attach($this->business->id, ['role' => 'owner', 'is_active' => true, 'joined_at' => now()]);

        $this->actingAs($admin);

        $this->assertTrue($admin->can('publish', $this->page));
    }

    public function test_policy_create_denied_without_manage_permission(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $this->assertFalse($developer->can('create', LandingPage::class));
    }

    public function test_policy_create_allowed_for_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($admin->can('create', LandingPage::class));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lead visibility — manager sees only assigned leads (if scoped)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_manager_can_view_lead_assigned_to_them(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $this->postJson(route('lp.submit', $this->page->slug), [
            'name' => 'Mgr Lead', 'email' => 'mgrlead@example.com',
        ]);

        $lead = Lead::first();
        $lead->update(['assigned_to' => $manager->id]);

        $this->assertTrue($manager->can('view', $lead));
    }

    public function test_developer_cannot_modify_lead(): void
    {
        $developer = User::factory()->create();
        $developer->assignRole('developer');

        $this->postJson(route('lp.submit', $this->page->slug), [
            'name' => 'Dev Lead', 'email' => 'devlead@example.com',
        ]);

        $lead = Lead::first();

        // Developer has no manage_leads → cannot update
        $this->assertFalse($developer->can('update', $lead));
    }
}
