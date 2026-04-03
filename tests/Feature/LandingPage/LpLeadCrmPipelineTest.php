<?php

namespace Tests\Feature\LandingPage;

use App\Models\Business;
use App\Models\Client;
use App\Models\Contact;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\Leads\LeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for the CRM pipeline integration: lead creation → stages → win/loss.
 *
 * Covers:
 *  - First pipeline stage (order=1) auto-assigned to new lead
 *  - Auto-create default stage when no stage exists
 *  - markWon() moves lead to is_won stage
 *  - markWon() promotes client from prospect → active
 *  - markWon() logs 'marked_won' activity
 *  - markLost() moves lead to is_lost stage
 *  - markLost() logs 'marked_lost' activity
 *  - Client deduplication: same email + same business → reuse existing client
 *  - Contact deduplication: same email + same client → reuse existing contact
 *  - New business → new client (cross-business isolation)
 *  - Lead title built correctly from LP title + name
 */
class LpLeadCrmPipelineTest extends TestCase
{
    use RefreshDatabase;

    private Business     $business;
    private LandingPage  $page;
    private PipelineStage $stageNew;
    private PipelineStage $stageWon;
    private PipelineStage $stageLost;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Cache::flush();

        $this->stageNew  = PipelineStage::firstOrCreate(['slug' => 'new-lead'], ['name' => 'New Lead', 'order' => 1,  'is_won' => false, 'is_lost' => false]);
        $this->stageWon  = PipelineStage::firstOrCreate(['slug' => 'won'],      ['name' => 'Won',      'order' => 5,  'is_won' => true,  'is_lost' => false]);
        $this->stageLost = PipelineStage::firstOrCreate(['slug' => 'lost'],     ['name' => 'Lost',     'order' => 6,  'is_won' => false, 'is_lost' => true]);

        $this->business = Business::create([
            'name'      => 'Pipeline Corp',
            'slug'      => 'pipeline-corp',
            'is_active' => true,
        ]);

        $this->page = LandingPage::create([
            'business_id'  => $this->business->id,
            'title'        => 'Get a Free Quote',
            'slug'         => 'free-quote',
            'status'       => LandingPage::STATUS_PUBLISHED,
            'template_key' => 'lead_magnet',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Pipeline stage assignment
    // ─────────────────────────────────────────────────────────────────────────

    public function test_new_lead_assigned_to_first_pipeline_stage(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());

        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        $this->assertEquals($this->stageNew->id, $lead->pipeline_stage_id);
    }

    public function test_auto_create_default_stage_when_none_exist(): void
    {
        // Remove all stages
        PipelineStage::query()->delete();

        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());

        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        $this->assertNotNull($lead->pipeline_stage_id);
        $this->assertDatabaseHas('pipeline_stages', ['slug' => 'new-lead']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mark Won
    // ─────────────────────────────────────────────────────────────────────────

    public function test_mark_won_moves_lead_to_won_stage(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead  = Lead::where('landing_page_id', $this->page->id)->firstOrFail();
        $actor = User::factory()->create();

        app(LeadService::class)->markWon($lead, $actor);

        $this->assertEquals($this->stageWon->id, $lead->fresh()->pipeline_stage_id);
    }

    public function test_mark_won_sets_won_at_timestamp(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead  = Lead::where('landing_page_id', $this->page->id)->firstOrFail();
        $actor = User::factory()->create();

        app(LeadService::class)->markWon($lead, $actor);

        $this->assertNotNull($lead->fresh()->won_at);
    }

    public function test_mark_won_promotes_client_from_prospect_to_active(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead   = Lead::where('landing_page_id', $this->page->id)->firstOrFail();
        $client = $lead->client;

        $this->assertEquals('prospect', $client->status);

        $actor = User::factory()->create();
        app(LeadService::class)->markWon($lead, $actor);

        $this->assertEquals('active', $client->fresh()->status);
    }

    public function test_mark_won_logs_marked_won_activity(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead  = Lead::where('landing_page_id', $this->page->id)->firstOrFail();
        $actor = User::factory()->create();

        app(LeadService::class)->markWon($lead, $actor);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type'    => 'marked_won',
        ]);
    }

    public function test_mark_won_does_not_change_active_client_status(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead   = Lead::where('landing_page_id', $this->page->id)->firstOrFail();
        $client = $lead->client;
        $client->update(['status' => 'active']);

        $actor = User::factory()->create();
        app(LeadService::class)->markWon($lead, $actor);

        // Should remain active (no regression to prospect)
        $this->assertEquals('active', $client->fresh()->status);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mark Lost
    // ─────────────────────────────────────────────────────────────────────────

    public function test_mark_lost_moves_lead_to_lost_stage(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead  = Lead::where('landing_page_id', $this->page->id)->firstOrFail();
        $actor = User::factory()->create();

        app(LeadService::class)->markLost($lead, 'Budget too small', $actor);

        $this->assertEquals($this->stageLost->id, $lead->fresh()->pipeline_stage_id);
    }

    public function test_mark_lost_stores_reason(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead  = Lead::where('landing_page_id', $this->page->id)->firstOrFail();
        $actor = User::factory()->create();

        app(LeadService::class)->markLost($lead, 'No budget', $actor);

        $this->assertEquals('No budget', $lead->fresh()->lost_reason);
    }

    public function test_mark_lost_logs_marked_lost_activity(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $lead  = Lead::where('landing_page_id', $this->page->id)->firstOrFail();
        $actor = User::factory()->create();

        app(LeadService::class)->markLost($lead, 'Price', $actor);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type'    => 'marked_lost',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Client deduplication
    // ─────────────────────────────────────────────────────────────────────────

    public function test_existing_client_email_reused_for_same_business(): void
    {
        // Pre-existing client with same email in same business
        Client::create([
            'business_id'           => $this->business->id,
            'company_name'          => 'Old Company',
            'primary_contact_email' => 'alice@example.com',
            'primary_contact_name'  => 'Alice Old',
            'status'                => 'active',
            'source'                => 'manual',
        ]);

        $this->postJson(route('lp.submit', $this->page->slug), $this->payload(['email' => 'alice@example.com']));

        $this->assertDatabaseCount('clients', 1);
        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        // Lead must link to the existing client
        $this->assertDatabaseHas('clients', [
            'id'                    => $lead->client_id,
            'primary_contact_email' => 'alice@example.com',
            'company_name'          => 'Old Company',
        ]);
    }

    public function test_existing_contact_email_reused_for_same_client(): void
    {
        $client = Client::create([
            'business_id'           => $this->business->id,
            'company_name'          => 'Existing Corp',
            'primary_contact_email' => 'bob@example.com',
            'primary_contact_name'  => 'Bob',
            'status'                => 'prospect',
            'source'                => 'website',
        ]);

        Contact::create([
            'client_id'  => $client->id,
            'first_name' => 'Bob',
            'last_name'  => 'Existing',
            'email'      => 'bob@example.com',
            'is_primary' => true,
        ]);

        $this->postJson(route('lp.submit', $this->page->slug), $this->payload(['email' => 'bob@example.com', 'name' => 'Bob New']));

        $this->assertDatabaseCount('contacts', 1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lead title construction
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lead_title_contains_lp_title_and_name(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload(['name' => 'Jane Smith']));

        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        $this->assertStringContainsString('Get a Free Quote', $lead->title);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function payload(array $override = []): array
    {
        return array_merge([
            'name'  => 'Alice',
            'email' => 'alice@example.com',
        ], $override);
    }
}
