<?php

namespace Tests\Feature\Leads;

use App\Events\LeadCaptured;
use App\Models\Business;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\LeadConsent;
use App\Models\LeadSource;
use App\Models\PipelineStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PublicLeadCaptureEndpointTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private LandingPage $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Cache::flush();

        PipelineStage::firstOrCreate(['slug' => 'new-lead'], ['name' => 'New Lead', 'order' => 1]);

        $this->business = Business::create([
            'name'      => 'Endpoint Corp',
            'slug'      => 'endpoint-corp',
            'is_active' => true,
        ]);

        $this->page = LandingPage::create([
            'business_id'  => $this->business->id,
            'title'        => 'Endpoint LP',
            'slug'         => 'endpoint-lp',
            'status'       => LandingPage::STATUS_PUBLISHED,
            'template_key' => 'lead_magnet',
        ]);
    }

    public function test_post_leads_creates_lead_and_links_it_to_landing_page_and_tenant(): void
    {
        $this->postJson(route('leads.capture'), $this->payload())
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'status'  => 'created',
            ]);

        $lead = Lead::query()->firstOrFail();

        $this->assertSame($this->page->id, $lead->landing_page_id);
        $this->assertSame($this->business->id, $lead->business_id);

        $this->assertDatabaseHas('lead_sources', [
            'lead_id'         => $lead->id,
            'type'            => 'landing_page',
            'business_id'     => $this->business->id,
            'landing_page_id' => $this->page->id,
        ]);
    }

    public function test_post_leads_creates_consent_record_when_checkbox_is_checked(): void
    {
        $this->postJson(route('leads.capture'), array_merge($this->payload(), [
            'consent' => true,
        ]))->assertCreated();

        $lead = Lead::query()->firstOrFail();

        $this->assertDatabaseHas('lead_consents', [
            'lead_id' => $lead->id,
            'given'   => true,
        ]);
    }

    public function test_post_leads_dispatches_crm_event(): void
    {
        Event::fake([LeadCaptured::class]);

        $this->postJson(route('leads.capture'), $this->payload())->assertCreated();

        Event::assertDispatched(LeadCaptured::class, function (LeadCaptured $event) {
            return $event->landingPage->is($this->page)
                && $event->lead->landing_page_id === $this->page->id
                && $event->lead->business_id === $this->business->id;
        });
    }

    public function test_post_leads_requires_landing_page_slug_and_email(): void
    {
        $this->postJson(route('leads.capture'), ['name' => 'Jan'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['landing_page_slug', 'email']);
    }

    public function test_post_leads_returns_404_for_unpublished_landing_page(): void
    {
        $this->page->update(['status' => LandingPage::STATUS_DRAFT]);

        $this->postJson(route('leads.capture'), $this->payload())
            ->assertNotFound();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'landing_page_slug' => $this->page->slug,
            'name'              => 'Jan Kowalski',
            'email'             => 'jan@example.com',
            'phone'             => '+48123123123',
            'message'           => 'Proszę o kontakt',
            'website'           => '',
        ], $override);
    }
}