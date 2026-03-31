<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportProjectsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user   = User::factory()->create();
        $this->client = Client::create([
            'company_name'          => 'Test Corp',
            'primary_contact_email' => 'test@example.com',
        ]);
    }

    private function makeProject(array $override = []): Project
    {
        return Project::create(array_merge([
            'title'     => 'Test Project',
            'client_id' => $this->client->id,
            'status'    => 'active',
        ], $override));
    }

    public function test_html_format_returns_200(): void
    {
        $this->makeProject();

        $this->actingAs($this->user)
            ->get(route('reports.projects.html'))
            ->assertOk()
            ->assertSee('Test Project');
    }

    public function test_pdf_format_returns_pdf_content_type(): void
    {
        $this->makeProject();

        $response = $this->actingAs($this->user)
            ->get(route('reports.projects.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_xlsx_format_returns_xlsx_content_type(): void
    {
        $this->makeProject();

        $response = $this->actingAs($this->user)
            ->get(route('reports.projects.xlsx'));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }

    public function test_csv_format_returns_csv_content_type(): void
    {
        $this->makeProject();

        $response = $this->actingAs($this->user)
            ->get(route('reports.projects.csv'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_status_filter_scopes_results(): void
    {
        $this->makeProject(['title' => 'Active Project', 'status' => 'active']);
        $this->makeProject(['title' => 'Draft Project',  'status' => 'draft']);

        $response = $this->actingAs($this->user)
            ->get(route('reports.projects.html', ['status' => 'active']));

        $response->assertOk()
            ->assertSee('Active Project')
            ->assertDontSee('Draft Project');
    }

    public function test_projects_count_is_shown_in_html(): void
    {
        $this->makeProject(['title' => 'Project One']);
        $this->makeProject(['title' => 'Project Two']);

        $this->actingAs($this->user)
            ->get(route('reports.projects.html'))
            ->assertOk()
            ->assertSee('Total projects')
            ->assertSee('2');
    }
}
