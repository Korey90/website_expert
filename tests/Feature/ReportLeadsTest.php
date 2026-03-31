<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportLeadsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private PipelineStage $stage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user  = User::factory()->create();
        $this->stage = PipelineStage::create(['name' => 'New', 'slug' => 'new', 'order' => 1]);
    }

    private function makeClient(string $company, string $email): Client
    {
        return Client::create(['company_name' => $company, 'primary_contact_email' => $email]);
    }

    private function makeLead(Client $client, array $override = []): Lead
    {
        return Lead::create(array_merge([
            'title'             => 'Test Lead',
            'client_id'         => $client->id,
            'pipeline_stage_id' => $this->stage->id,
            'source'            => 'contact_form',
        ], $override));
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get(route('reports.leads.html'))->assertRedirect(route('login'));
    }

    public function test_html_format_returns_200(): void
    {
        $client = $this->makeClient('Test Corp', 'test@example.com');
        $this->makeLead($client);

        $this->actingAs($this->user)
            ->get(route('reports.leads.html'))
            ->assertOk()
            ->assertSee('Test Corp');
    }

    public function test_pdf_format_returns_pdf_content_type(): void
    {
        $client = $this->makeClient('Test Corp', 'test@example.com');
        $this->makeLead($client);

        $response = $this->actingAs($this->user)
            ->get(route('reports.leads.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_xlsx_format_returns_xlsx_content_type(): void
    {
        $client = $this->makeClient('Test Corp', 'test@example.com');
        $this->makeLead($client);

        $response = $this->actingAs($this->user)
            ->get(route('reports.leads.xlsx'));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }

    public function test_csv_format_returns_csv_content_type(): void
    {
        $client = $this->makeClient('Test Corp', 'test@example.com');
        $this->makeLead($client);

        $response = $this->actingAs($this->user)
            ->get(route('reports.leads.csv'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_stage_id_filter_scopes_results(): void
    {
        $stageB = PipelineStage::create(['name' => 'Qualified', 'slug' => 'qualified', 'order' => 2]);

        $clientA = $this->makeClient('Alpha Corp', 'alpha@example.com');
        $clientB = $this->makeClient('Beta Corp', 'beta@example.com');

        $this->makeLead($clientA, ['pipeline_stage_id' => $this->stage->id]);
        $this->makeLead($clientB, ['pipeline_stage_id' => $stageB->id]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.leads.html', ['stage_id' => $this->stage->id]));

        $response->assertOk()
            ->assertSee('Alpha Corp')
            ->assertDontSee('Beta Corp');
    }

    public function test_empty_leads_returns_200(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.leads.html'))
            ->assertOk();
    }
}
