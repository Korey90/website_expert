<?php

namespace Tests\Feature\LandingPage;

use App\Models\Business;
use App\Models\Client;
use App\Models\Contact;
use App\Models\Land;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadConsent;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use App\Events\LeadCaptured;
use Tests\TestCase;

/**
 * Feature tests for the public landing page lead capture endpoint.
 *
 * Covers:
 *  - Happy path: valid submission creates Lead, Client, Contact, LeadSource, LeadConsent
 *  - Source field set to 'landing_page'
 *  - business_id propagation from LP to lead
 *  - lp_default_assignee applied to lead.assigned_to
 *  - form_data stored on lead
 *  - UTM parameters captured
 *  - 422 for missing email
 *  - 422 for invalid email
 *  - honeypot field blocks bot submission
 *  - throttle: 4th submit within window → 429
 *  - draft/archived LP returns 404
 *  - LeadCaptured event dispatched
 *  - success JSON shape
 */
class PublicLeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    private Business     $business;
    private User         $assignee;
    private LandingPage  $page;
    private PipelineStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Cache::flush();

        $this->stage = PipelineStage::firstOrCreate(
            ['slug'  => 'new-lead'],
            ['name'  => 'New Lead', 'order' => 1]
        );

        $this->assignee = User::factory()->create(['is_active' => true]);
        $this->assignee->assignRole('manager');

        $this->business = Business::create([
            'name'      => 'Acme Ltd',
            'slug'      => 'acme-ltd',
            'is_active' => true,
        ]);

        $this->page = LandingPage::create([
            'business_id'         => $this->business->id,
            'default_assignee_id' => $this->assignee->id,
            'title'               => 'Test LP',
            'slug'                => 'test-lp',
            'status'              => LandingPage::STATUS_PUBLISHED,
            'template_key'        => 'lead_magnet',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Happy path
    // ─────────────────────────────────────────────────────────────────────────

    public function test_valid_submission_creates_lead(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload())
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('leads', [
            'source'          => 'landing_page',
            'landing_page_id' => $this->page->id,
            'business_id'     => $this->business->id,
        ]);
    }

    public function test_valid_submission_creates_client(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload());

        $this->assertDatabaseHas('clients', [
            'primary_contact_email' => 'john@example.com',
            'status'                => 'prospect',
        ]);
    }

    public function test_valid_submission_creates_contact(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload());

        $client = Client::where('primary_contact_email', 'john@example.com')->firstOrFail();

        $this->assertDatabaseHas('contacts', [
            'client_id'  => $client->id,
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
        ]);
    }

    public function test_valid_submission_creates_lead_source(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload());

        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        $this->assertDatabaseHas('lead_sources', [
            'lead_id'         => $lead->id,
            'business_id'     => $this->business->id,
            'type'            => 'landing_page',
            'landing_page_id' => $this->page->id,
        ]);
    }

    public function test_submission_with_consent_creates_lead_consent(): void
    {
        $this->postJson(
            route('lp.submit', $this->page->slug),
            array_merge($this->validPayload(), ['consent' => true])
        );

        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        $this->assertDatabaseHas('lead_consents', [
            'lead_id' => $lead->id,
            'given'   => true,
        ]);
    }

    public function test_submission_without_consent_does_not_create_consent_record(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload());

        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        $this->assertDatabaseMissing('lead_consents', ['lead_id' => $lead->id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Field propagation
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lead_source_is_landing_page(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload());

        $this->assertDatabaseHas('leads', ['source' => 'landing_page']);
    }

    public function test_business_id_propagated_to_lead(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload());

        $this->assertDatabaseHas('leads', [
            'business_id'     => $this->business->id,
            'landing_page_id' => $this->page->id,
        ]);
    }

    public function test_default_assignee_applied_to_lead(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload());

        $this->assertDatabaseHas('leads', [
            'landing_page_id' => $this->page->id,
            'assigned_to'     => $this->assignee->id,
        ]);
    }

    public function test_pipeline_stage_assigned_to_lead(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload());

        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        $this->assertNotNull($lead->pipeline_stage_id);
        $this->assertEquals($this->stage->id, $lead->pipeline_stage_id);
    }

    public function test_utm_parameters_stored_on_lead(): void
    {
        $this->postJson(
            route('lp.submit', $this->page->slug) . '?utm_source=google&utm_medium=cpc&utm_campaign=spring2026',
            $this->validPayload()
        );

        $this->assertDatabaseHas('lead_sources', [
            'utm_source'   => 'google',
            'utm_medium'   => 'cpc',
            'utm_campaign' => 'spring2026',
        ]);
    }

    public function test_form_data_stored_when_extra_fields_present(): void
    {
        $payload = array_merge($this->validPayload(), [
            'company' => 'Acme Corp',
            'phone'   => '+447700900000',
            'message' => 'Tell me more',
        ]);

        $this->postJson(route('lp.submit', $this->page->slug), $payload)
            ->assertOk();

        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        // 'message' from validated payload is mapped to lead.notes
        $this->assertEquals('Tell me more', $lead->notes);
    }

    public function test_activity_log_created_for_new_lead(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload());

        $lead = Lead::where('landing_page_id', $this->page->id)->firstOrFail();

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type'    => 'created',
        ]);

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type'    => 'lp_captured',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Validation errors
    // ─────────────────────────────────────────────────────────────────────────

    public function test_missing_email_returns_422(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), ['name' => 'John'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_invalid_email_returns_422(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_message_too_long_returns_422(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), array_merge(
            $this->validPayload(),
            ['message' => str_repeat('x', 2001)]
        ))->assertStatus(422)
          ->assertJsonValidationErrors(['message']);
    }

    public function test_invalid_phone_format_returns_422(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), array_merge(
            $this->validPayload(),
            ['phone' => 'not-a-phone!!']
        ))->assertStatus(422)
          ->assertJsonValidationErrors(['phone']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Honeypot
    // ─────────────────────────────────────────────────────────────────────────

    public function test_honeypot_field_filled_blocks_submission(): void
    {
        $this->postJson(
            route('lp.submit', $this->page->slug),
            array_merge($this->validPayload(), ['website' => 'http://spam.com'])
        )->assertStatus(422);

        $this->assertDatabaseMissing('leads', ['source' => 'landing_page']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Page status guards
    // ─────────────────────────────────────────────────────────────────────────

    public function test_draft_page_returns_404(): void
    {
        $draft = LandingPage::create([
            'business_id'  => $this->business->id,
            'title'        => 'Draft LP',
            'slug'         => 'draft-lp',
            'status'       => LandingPage::STATUS_DRAFT,
            'template_key' => 'lead_magnet',
        ]);

        $this->postJson(route('lp.submit', $draft->slug), $this->validPayload())
            ->assertNotFound();
    }

    public function test_archived_page_returns_404(): void
    {
        $archived = LandingPage::create([
            'business_id'  => $this->business->id,
            'title'        => 'Archived LP',
            'slug'         => 'archived-lp',
            'status'       => LandingPage::STATUS_ARCHIVED,
            'template_key' => 'lead_magnet',
        ]);

        $this->postJson(route('lp.submit', $archived->slug), $this->validPayload())
            ->assertNotFound();
    }

    public function test_nonexistent_slug_returns_404(): void
    {
        $this->postJson(route('lp.submit', 'does-not-exist'), $this->validPayload())
            ->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Event
    // ─────────────────────────────────────────────────────────────────────────

    public function test_lead_captured_event_dispatched(): void
    {
        Event::fake([LeadCaptured::class]);

        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload());

        Event::assertDispatched(LeadCaptured::class, function (LeadCaptured $event) {
            return $event->lead->source === 'landing_page'
                && $event->landingPage->id === $this->page->id;
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Throttle
    // ─────────────────────────────────────────────────────────────────────────

    public function test_rate_limit_blocks_fourth_submission(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->postJson(
                route('lp.submit', $this->page->slug),
                // Different emails to avoid dedup cache blocking
                array_merge($this->validPayload(), ['email' => "user{$i}@example.com"])
            )->assertOk();
        }

        // 4th attempt from same IP within 60 minutes
        $this->postJson(
            route('lp.submit', $this->page->slug),
            array_merge($this->validPayload(), ['email' => 'user4@example.com'])
        )->assertStatus(429);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Response shape
    // ─────────────────────────────────────────────────────────────────────────

    public function test_success_response_structure(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload())
            ->assertOk()
            ->assertJsonStructure(['success', 'message']);
    }

    public function test_conversions_count_incremented_on_new_lead(): void
    {
        $before = $this->page->conversions_count;

        $this->postJson(route('lp.submit', $this->page->slug), $this->validPayload());

        $this->assertEquals($before + 1, $this->page->fresh()->conversions_count);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function validPayload(array $override = []): array
    {
        return array_merge([
            'name'  => 'John Doe',
            'email' => 'john@example.com',
        ], $override);
    }
}
