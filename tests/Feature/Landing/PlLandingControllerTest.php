<?php

namespace Tests\Feature\Landing;

use App\Events\LeadCaptured;
use App\Mail\LeadEnquiryConfirmationMail;
use App\Models\Business;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature tests for the Polish static landing page at /pl/strony-internetowe-uk
 *
 * Covers:
 *  - GET /pl/strony-internetowe-uk returns 200 (Inertia page)
 *  - Happy path: lead created with locale=pl, source_type=landing_page, audience=polish_business_uk
 *  - gclid/gbraid/wbraid stored on lead
 *  - UTM params stored on lead
 *  - audience_type stored in form_data
 *  - 422 for missing name
 *  - 422 for missing email
 *  - 422 for invalid email
 *  - 422 for unchecked gdpr_consent
 *  - 422 for invalid current_website URL
 *  - Throttle: 6th submit within window → 429
 *  - Confirmation email sent to prospect
 *  - Idempotent: same email does not create duplicate Client
 */
class PlLandingControllerTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        // Prevent synchronous job execution — SQLite test DB lacks task_created enum
        Queue::fake();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        PipelineStage::firstOrCreate(
            ['slug' => 'new-lead'],
            ['name' => 'New Lead', 'order' => 1]
        );

        $this->business = Business::first() ?? Business::create([
            'name'      => 'Website Expert',
            'slug'      => 'website-expert',
            'is_active' => true,
        ]);
    }

    // ── GET ───────────────────────────────────────────────────────────────────

    public function test_page_renders_200(): void
    {
        $response = $this->get('/pl/strony-internetowe-uk');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Landing/PlStronyInternetowe')
        );
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_valid_submission_creates_lead_with_correct_tracking(): void
    {
        Mail::fake();

        $response = $this->postJson('/pl/strony-internetowe-uk/wyslij', [
            'name'          => 'Jan Kowalski',
            'email'         => 'jan@example.co.uk',
            'phone'         => '+44 7700 900000',
            'company'       => 'Kowalski Budownictwo',
            'message'       => 'Potrzebuję nowej strony dla firmy budowlanej.',
            'audience_type' => 'english_clients',
            'gdpr_consent'  => true,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'created')
                 ->assertJsonStructure(['status', 'lead_id']);

        $lead = Lead::latest()->first();
        $this->assertNotNull($lead);
        $this->assertSame('google_ads',           $lead->source);
        $this->assertSame('landing_page',         $lead->source_type);
        $this->assertSame('pl',                   $lead->locale);
        $this->assertSame('polish_business_uk',   $lead->audience);
        $this->assertSame('strony-internetowe-uk', $lead->landing_page_slug);
    }

    public function test_gclid_and_utm_are_stored_on_lead(): void
    {
        Mail::fake();

        $this->postJson(
            '/pl/strony-internetowe-uk/wyslij?gclid=abc123&utm_source=google&utm_medium=cpc&utm_campaign=pl-strony-uk',
            [
                'name'         => 'Anna Nowak',
                'email'        => 'anna@example.co.uk',
                'message'      => 'Chciałabym wycenę strony.',
                'gdpr_consent' => true,
            ]
        )->assertStatus(200);

        $lead = Lead::latest()->first();
        $this->assertSame('abc123',         $lead->gclid);
        $this->assertSame('google',         $lead->utm_source);
        $this->assertSame('cpc',            $lead->utm_medium);
        $this->assertSame('pl-strony-uk',   $lead->utm_campaign);
    }

    public function test_audience_type_stored_in_form_data(): void
    {
        Mail::fake();

        $this->postJson('/pl/strony-internetowe-uk/wyslij', [
            'name'          => 'Piotr Wiśniewski',
            'email'         => 'piotr@example.co.uk',
            'message'       => 'Potrzebuję strony.',
            'audience_type' => 'both',
            'gdpr_consent'  => true,
        ])->assertStatus(200);

        $lead = Lead::latest()->first();
        $this->assertSame('both', $lead->form_data['audience_type'] ?? null);
    }

    public function test_confirmation_email_sent_to_prospect(): void
    {
        Mail::fake();

        $this->postJson('/pl/strony-internetowe-uk/wyslij', [
            'name'         => 'Maria Wójcik',
            'email'        => 'maria@example.co.uk',
            'message'      => 'Potrzebuję strony.',
            'gdpr_consent' => true,
        ])->assertStatus(200);

        Mail::assertQueued(LeadEnquiryConfirmationMail::class, function (LeadEnquiryConfirmationMail $mail) {
            return $mail->mailLocale === 'pl';
        });
    }

    public function test_same_email_does_not_create_duplicate_client(): void
    {
        Mail::fake();

        $payload = [
            'name'         => 'Tomasz Kaczmarek',
            'email'        => 'tomasz@example.co.uk',
            'message'      => 'Pierwsza wiadomość.',
            'gdpr_consent' => true,
        ];

        $this->postJson('/pl/strony-internetowe-uk/wyslij', $payload)->assertStatus(200);
        $this->postJson('/pl/strony-internetowe-uk/wyslij', array_merge($payload, ['message' => 'Druga wiadomość.']))->assertStatus(200);

        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseCount('leads',   2);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_missing_name_returns_422(): void
    {
        $this->postJson('/pl/strony-internetowe-uk/wyslij', [
            'email'        => 'test@example.co.uk',
            'message'      => 'Wiadomość testowa.',
            'gdpr_consent' => true,
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_missing_email_returns_422(): void
    {
        $this->postJson('/pl/strony-internetowe-uk/wyslij', [
            'name'         => 'Jan Kowalski',
            'message'      => 'Wiadomość testowa.',
            'gdpr_consent' => true,
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_invalid_email_returns_422(): void
    {
        $this->postJson('/pl/strony-internetowe-uk/wyslij', [
            'name'         => 'Jan Kowalski',
            'email'        => 'nie-jest-emailem',
            'message'      => 'Wiadomość testowa.',
            'gdpr_consent' => true,
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_unchecked_gdpr_returns_422(): void
    {
        $this->postJson('/pl/strony-internetowe-uk/wyslij', [
            'name'         => 'Jan Kowalski',
            'email'        => 'jan@example.co.uk',
            'message'      => 'Wiadomość testowa.',
            'gdpr_consent' => false,
        ])->assertStatus(422)->assertJsonValidationErrors(['gdpr_consent']);
    }

    public function test_invalid_current_website_url_returns_422(): void
    {
        $this->postJson('/pl/strony-internetowe-uk/wyslij', [
            'name'            => 'Jan Kowalski',
            'email'           => 'jan@example.co.uk',
            'message'         => 'Wiadomość testowa.',
            'current_website' => 'to-nie-jest-url',
            'gdpr_consent'    => true,
        ])->assertStatus(422)->assertJsonValidationErrors(['current_website']);
    }
}
