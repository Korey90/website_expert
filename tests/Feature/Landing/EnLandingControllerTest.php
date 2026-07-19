<?php

namespace Tests\Feature\Landing;

use App\Mail\LeadEnquiryConfirmationMail;
use App\Models\Business;
use App\Models\Lead;
use App\Models\PipelineStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Feature tests for the English static landing page at /en/web-design-northern-ireland
 *
 * Covers:
 *  - GET /en/web-design-northern-ireland returns 200 (Inertia page)
 *  - Happy path: lead created with locale=en, source_type=landing_page, audience=ni_business
 *  - gclid/UTM stored on lead
 *  - 422 for missing name / email / message / gdpr
 *  - 422 for invalid current_website URL
 *  - Confirmation email sent in English
 *  - Idempotent: same email does not create duplicate Client
 */
class EnLandingControllerTest extends TestCase
{
    use RefreshDatabase;

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

        Business::first() ?? Business::create([
            'name'      => 'Website Expert',
            'slug'      => 'website-expert',
            'is_active' => true,
        ]);
    }

    // ── GET ───────────────────────────────────────────────────────────────────

    public function test_page_renders_200(): void
    {
        $this->get('/en/web-design-northern-ireland')
             ->assertStatus(200)
             ->assertInertia(fn ($page) => $page->component('Landing/EnWebDesign'));
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_valid_submission_creates_lead_with_correct_tracking(): void
    {
        Mail::fake();

        $response = $this->postJson('/en/web-design-northern-ireland/submit', [
            'name'         => 'Jane Smith',
            'email'        => 'jane@example.co.uk',
            'phone'        => '+44 7700 900000',
            'message'      => 'I need a new website for my plumbing company.',
            'gdpr_consent' => true,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'created')
                 ->assertJsonStructure(['status', 'lead_id']);

        $lead = Lead::latest()->first();
        $this->assertNotNull($lead);
        $this->assertSame('google_ads',                  $lead->source);
        $this->assertSame('landing_page',                $lead->source_type);
        $this->assertSame('en',                          $lead->locale);
        $this->assertSame('ni_business',                 $lead->audience);
        $this->assertSame('web-design-northern-ireland', $lead->landing_page_slug);
    }

    public function test_gclid_and_utm_are_stored(): void
    {
        Mail::fake();

        $this->postJson(
            '/en/web-design-northern-ireland/submit?gclid=xyz789&utm_source=google&utm_medium=cpc&utm_campaign=ni-web-design',
            [
                'name'         => 'Tom Hughes',
                'email'        => 'tom@example.co.uk',
                'message'      => 'Need a website.',
                'gdpr_consent' => true,
            ]
        )->assertStatus(200);

        $lead = Lead::latest()->first();
        $this->assertSame('xyz789',        $lead->gclid);
        $this->assertSame('google',        $lead->utm_source);
        $this->assertSame('cpc',           $lead->utm_medium);
        $this->assertSame('ni-web-design', $lead->utm_campaign);
    }

    public function test_confirmation_email_sent_in_english(): void
    {
        Mail::fake();

        $this->postJson('/en/web-design-northern-ireland/submit', [
            'name'         => 'Sarah Connor',
            'email'        => 'sarah@example.co.uk',
            'message'      => 'Need a website for my business.',
            'gdpr_consent' => true,
        ])->assertStatus(200);

        Mail::assertQueued(LeadEnquiryConfirmationMail::class, function (LeadEnquiryConfirmationMail $mail) {
            return $mail->mailLocale === 'en';
        });
    }

    public function test_same_email_does_not_create_duplicate_client(): void
    {
        Mail::fake();

        $payload = [
            'name'         => 'Mark Brown',
            'email'        => 'mark@example.co.uk',
            'message'      => 'First enquiry.',
            'gdpr_consent' => true,
        ];

        $this->postJson('/en/web-design-northern-ireland/submit', $payload)->assertStatus(200);
        $this->postJson('/en/web-design-northern-ireland/submit', array_merge($payload, ['message' => 'Second enquiry.']))->assertStatus(200);

        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseCount('leads',   2);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_missing_name_returns_422(): void
    {
        $this->postJson('/en/web-design-northern-ireland/submit', [
            'email'        => 'test@example.co.uk',
            'message'      => 'Test message.',
            'gdpr_consent' => true,
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_missing_email_returns_422(): void
    {
        $this->postJson('/en/web-design-northern-ireland/submit', [
            'name'         => 'John Doe',
            'message'      => 'Test message.',
            'gdpr_consent' => true,
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_missing_message_returns_422(): void
    {
        $this->postJson('/en/web-design-northern-ireland/submit', [
            'name'         => 'John Doe',
            'email'        => 'john@example.co.uk',
            'gdpr_consent' => true,
        ])->assertStatus(422)->assertJsonValidationErrors(['message']);
    }

    public function test_unchecked_gdpr_returns_422(): void
    {
        $this->postJson('/en/web-design-northern-ireland/submit', [
            'name'         => 'John Doe',
            'email'        => 'john@example.co.uk',
            'message'      => 'Test message.',
            'gdpr_consent' => false,
        ])->assertStatus(422)->assertJsonValidationErrors(['gdpr_consent']);
    }

    public function test_invalid_current_website_url_returns_422(): void
    {
        $this->postJson('/en/web-design-northern-ireland/submit', [
            'name'            => 'John Doe',
            'email'           => 'john@example.co.uk',
            'message'         => 'Test message.',
            'current_website' => 'not-a-url',
            'gdpr_consent'    => true,
        ])->assertStatus(422)->assertJsonValidationErrors(['current_website']);
    }
}
