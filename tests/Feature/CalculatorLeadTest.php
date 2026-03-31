<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CalculatorLeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        PipelineStage::create(['name' => 'New Lead', 'slug' => 'new-lead', 'order' => 1]);
    }

    private function validPayload(array $override = []): array
    {
        return array_merge([
            'contactEmail' => 'anna@example.com',
            'companyName'  => 'Test Corp',
        ], $override);
    }

    public function test_valid_submission_creates_lead_and_client(): void
    {
        $this->postJson(route('calculator.lead'), $this->validPayload())
            ->assertStatus(201)
            ->assertJsonStructure(['message', 'lead_id']);

        $this->assertDatabaseHas('clients', ['primary_contact_email' => 'anna@example.com']);
        $this->assertDatabaseHas('leads',   ['source' => 'calculator']);
    }

    public function test_repeated_submission_reuses_existing_client(): void
    {
        Client::create([
            'company_name'          => 'Test Corp',
            'primary_contact_email' => 'anna@example.com',
            'primary_contact_name'  => 'Test Corp',
        ]);

        $this->postJson(route('calculator.lead'), $this->validPayload())
            ->assertStatus(201);

        $this->assertCount(1, Client::where('primary_contact_email', 'anna@example.com')->get());
    }

    public function test_missing_email_returns_422(): void
    {
        $this->postJson(route('calculator.lead'), $this->validPayload(['contactEmail' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contactEmail']);
    }

    public function test_invalid_email_returns_422(): void
    {
        $this->postJson(route('calculator.lead'), $this->validPayload(['contactEmail' => 'not-an-email']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contactEmail']);
    }

    public function test_missing_contact_email_returns_422(): void
    {
        $this->postJson(route('calculator.lead'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contactEmail']);
    }

    public function test_calculator_data_stored_with_lead(): void
    {
        $payload = $this->validPayload([
            'projectType'   => 'ecommerce',
            'estimateLow'   => 5000,
            'estimateHigh'  => 10000,
        ]);

        $response = $this->postJson(route('calculator.lead'), $payload)->assertStatus(201);

        $leadId = $response->json('lead_id');
        $lead   = \App\Models\Lead::find($leadId);

        $this->assertNotNull($lead);
        $this->assertNotNull($lead->calculator_data);
    }
}
