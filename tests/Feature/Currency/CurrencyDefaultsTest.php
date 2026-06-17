<?php

namespace Tests\Feature\Currency;

use App\Actions\CreateLeadAction;
use App\Models\Client;
use App\Models\PipelineStage;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyDefaultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        PipelineStage::create([
            'name' => 'New Lead',
            'slug' => 'new-lead',
            'order' => 1,
        ]);
    }

    public function test_create_lead_uses_currency_and_country_from_locale(): void
    {
        app()->setLocale('pl');

        $lead = app(CreateLeadAction::class)->execute([
            'name' => 'Anna Kowalska',
            'email' => 'anna@example.com',
            'company' => 'Kowalska Studio',
            'source' => 'contact_form',
            'value' => 1200,
        ]);

        $this->assertSame('PLN', $lead->currency);
        $this->assertSame('PLN', $lead->client->currency);
        $this->assertSame('PL', $lead->client->country);
    }

    public function test_explicit_currency_wins_over_locale(): void
    {
        app()->setLocale('pl');

        $lead = app(CreateLeadAction::class)->execute([
            'name' => 'Patricia Smith',
            'email' => 'patricia@example.com',
            'company' => 'Smith Studio',
            'source' => 'contact_form',
            'currency' => 'EUR',
            'value' => 900,
        ]);

        $this->assertSame('EUR', $lead->currency);
        $this->assertSame('EUR', $lead->client->currency);
    }

    public function test_currency_defaults_on_new_models_from_locale(): void
    {
        app()->setLocale('pt');

        $client = Client::create([
            'company_name' => 'Lisbon Studio',
            'primary_contact_email' => 'hello@lisbon.test',
        ]);

        $project = Project::create([
            'title' => 'Website Refresh',
            'client_id' => $client->id,
            'status' => 'draft',
        ]);

        $this->assertSame('EUR', $client->currency);
        $this->assertSame('EUR', $project->currency);
    }
}
