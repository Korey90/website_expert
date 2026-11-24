<?php

namespace Tests\Feature\AI;

use App\Models\AiAssistant;
use App\Models\Business;
use App\Models\LeadConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicAiChatTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private AiAssistant $assistant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        $this->business = Business::create([
            'name' => 'Public AI Test Agency',
            'slug' => 'public-ai-test-agency',
            'is_active' => true,
            'locale' => 'en',
        ]);

        $this->assistant = new AiAssistant;
        $this->assistant->forceFill([
            'business_id' => $this->business->id,
            'name' => 'Test Assistant',
            'enabled' => true,
            'locale' => 'en',
        ])->save();

        config()->set('ai_assistant.enabled', true);
        config()->set('ai_assistant.retention_enabled', true);
        config()->set('ai_assistant.public_business_id', $this->business->id);
    }

    public function test_public_widget_is_off_when_retention_is_disabled(): void
    {
        config()->set('ai_assistant.retention_enabled', false);

        $this->getJson('/ai/bootstrap')
            ->assertOk()
            ->assertJsonPath('enabled', false);

        $this->postJson('/ai/message', [
            'message' => 'What services do you offer?',
            'locale' => 'en',
            'client_message_id' => (string) Str::uuid(),
        ])->assertNotFound();
    }

    public function test_public_visitor_cannot_select_another_tenant(): void
    {
        $beta = Business::create([
            'name' => 'Other Agency', 'slug' => 'another-ai-agency', 'is_active' => true,
        ]);

        $this->postJson('/ai/message', [
            'message' => 'What is your website price?',
            'locale' => 'en',
            'client_message_id' => (string) Str::uuid(),
            'business_id' => $beta->id, // Intentionally untrusted client data.
        ])->assertOk()->assertJsonPath('status', 'unknown');

        $this->assertDatabaseHas('ai_conversations', ['business_id' => $this->business->id]);
        $this->assertDatabaseMissing('ai_conversations', ['business_id' => $beta->id]);
    }

    public function test_unknown_question_is_recorded_for_manual_review(): void
    {
        $this->postJson('/ai/message', [
            'message' => 'Do you sell marine engines?',
            'locale' => 'en',
            'client_message_id' => (string) Str::uuid(),
        ])->assertOk()->assertJsonPath('status', 'unknown');

        $this->assertDatabaseCount('ai_unanswered_questions', 1);
        $this->assertDatabaseCount('ai_messages', 2);
    }

    public function test_lead_requires_explicit_consent(): void
    {
        $this->postJson('/ai/lead', [
            'name' => 'No Consent',
            'email' => 'no-consent@example.test',
            'project' => 'Need a new web application project',
            'locale' => 'en',
            'consent' => false,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('ai_leads', 0);
    }

    public function test_consenting_visitor_creates_lead_using_existing_mysql_source_enum(): void
    {
        $payload = [
            'name' => 'Ada Example',
            'email' => 'ada@example.test',
            'project' => 'I would like a company website and a quote.',
            'locale' => 'en',
            'consent' => true,
        ];

        $this->postJson('/ai/lead', $payload)
            ->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('leads', [
            'business_id' => $this->business->id,
            'source' => 'other',
            'source_type' => 'ai_chat',
        ]);
        $this->assertDatabaseCount('ai_leads', 1);
        $this->assertSame(1, LeadConsent::count());
    }
}
