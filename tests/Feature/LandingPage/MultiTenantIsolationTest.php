<?php

namespace Tests\Feature\LandingPage;

use App\Models\Business;
use App\Models\Client;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for multi-tenant data isolation in the LP → CRM pipeline.
 *
 * Covers:
 *  - Same email submitted on LP from Business A and Business B → 2 separate clients
 *  - Client created via BusinessA LP has business_id = BusinessA (not BusinessB)
 *  - Lead created via LP has business_id matching the LP's business
 *  - LeadSource.business_id matches Lead.business_id
 *  - LP from Business A cannot produce a Client with Business B's ID
 *  - Deduplication is scoped per-business (same email, different business → 2 leads)
 *  - LP without a business_id → lead.business_id = null (graceful degradation)
 */
class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Business    $businessA;
    private Business    $businessB;
    private LandingPage $pageA;
    private LandingPage $pageB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Cache::flush();

        PipelineStage::firstOrCreate(['slug' => 'new-lead'], ['name' => 'New Lead', 'order' => 1]);

        $this->businessA = Business::create([
            'name'      => 'Company Alpha',
            'slug'      => 'company-alpha',
            'is_active' => true,
        ]);

        $this->businessB = Business::create([
            'name'      => 'Company Beta',
            'slug'      => 'company-beta',
            'is_active' => true,
        ]);

        $this->pageA = LandingPage::create([
            'business_id'  => $this->businessA->id,
            'title'        => 'Alpha LP',
            'slug'         => 'alpha-lp',
            'status'       => LandingPage::STATUS_PUBLISHED,
            'template_key' => 'lead_magnet',
        ]);

        $this->pageB = LandingPage::create([
            'business_id'  => $this->businessB->id,
            'title'        => 'Beta LP',
            'slug'         => 'beta-lp',
            'status'       => LandingPage::STATUS_PUBLISHED,
            'template_key' => 'lead_magnet',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Client isolation
    // ─────────────────────────────────────────────────────────────────────────

    public function test_same_email_on_different_business_lps_creates_two_clients(): void
    {
        $email = 'shared@example.com';

        $this->postJson(route('lp.submit', $this->pageA->slug), [
            'name' => 'Shared User', 'email' => $email,
        ]);

        Cache::flush(); // ensure no dedup block across businesses

        $this->postJson(route('lp.submit', $this->pageB->slug), [
            'name' => 'Shared User', 'email' => $email,
        ]);

        $this->assertDatabaseCount('clients', 2);
        $this->assertDatabaseHas('clients', [
            'primary_contact_email' => $email,
            'business_id'           => $this->businessA->id,
        ]);
        $this->assertDatabaseHas('clients', [
            'primary_contact_email' => $email,
            'business_id'           => $this->businessB->id,
        ]);
    }

    public function test_client_business_id_matches_landing_page_business(): void
    {
        $this->postJson(route('lp.submit', $this->pageA->slug), [
            'name' => 'Alpha Client', 'email' => 'alpha@example.com',
        ]);

        $client = Client::where('primary_contact_email', 'alpha@example.com')->firstOrFail();

        $this->assertEquals($this->businessA->id, $client->business_id);
        $this->assertNotEquals($this->businessB->id, $client->business_id);
    }

    public function test_client_not_shared_between_businesses(): void
    {
        // Client created for Business A
        $this->postJson(route('lp.submit', $this->pageA->slug), [
            'name' => 'Isolated Client', 'email' => 'isolated@example.com',
        ]);

        // Business B should have zero clients
        $this->assertEquals(
            0,
            Client::where('business_id', $this->businessB->id)->count()
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lead isolation
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lead_business_id_matches_lp_business(): void
    {
        $this->postJson(route('lp.submit', $this->pageA->slug), [
            'name' => 'Lead A', 'email' => 'leada@example.com',
        ]);

        $lead = Lead::where('landing_page_id', $this->pageA->id)->firstOrFail();

        $this->assertEquals($this->businessA->id, $lead->business_id);
    }

    public function test_lead_does_not_cross_to_other_business(): void
    {
        $this->postJson(route('lp.submit', $this->pageA->slug), [
            'name' => 'Tenant Check', 'email' => 'check@example.com',
        ]);

        $this->assertEquals(0, Lead::where('business_id', $this->businessB->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LeadSource isolation
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lead_source_business_id_matches_lead_business_id(): void
    {
        $this->postJson(route('lp.submit', $this->pageA->slug), [
            'name' => 'Source Check', 'email' => 'source@example.com',
        ]);

        $lead   = Lead::where('landing_page_id', $this->pageA->id)->firstOrFail();
        $source = LeadSource::where('lead_id', $lead->id)->firstOrFail();

        $this->assertEquals($lead->business_id, $source->business_id);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Deduplication is scoped per-business
    // ─────────────────────────────────────────────────────────────────────────

    public function test_same_email_same_lp_same_business_deduplicates(): void
    {
        $payload = ['name' => 'Dup User', 'email' => 'dup@example.com'];

        $this->postJson(route('lp.submit', $this->pageA->slug), $payload);
        $this->postJson(route('lp.submit', $this->pageA->slug), $payload);

        $this->assertEquals(
            1,
            Lead::where('business_id', $this->businessA->id)
                ->where('landing_page_id', $this->pageA->id)
                ->count()
        );
    }

    public function test_same_email_different_business_lp_creates_two_leads(): void
    {
        $payload = ['name' => 'Multi User', 'email' => 'multi@example.com'];

        $this->postJson(route('lp.submit', $this->pageA->slug), $payload);

        Cache::flush();

        $this->postJson(route('lp.submit', $this->pageB->slug), $payload);

        $this->assertEquals(1, Lead::where('business_id', $this->businessA->id)->count());
        $this->assertEquals(1, Lead::where('business_id', $this->businessB->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LP without business (graceful degradation)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_landing_page_requires_business_id(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        LandingPage::create([
            'business_id'  => null,
            'title'        => 'Unbound LP',
            'slug'         => 'unbound-lp',
            'status'       => LandingPage::STATUS_PUBLISHED,
            'template_key' => 'lead_magnet',
        ]);
    }
}
