<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportInvoicesTest extends TestCase
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

    private function makeInvoice(array $override = []): Invoice
    {
        static $seq = 1;

        return Invoice::create(array_merge([
            'number'     => 'INV-' . str_pad($seq++, 3, '0', STR_PAD_LEFT),
            'client_id'  => $this->client->id,
            'created_by' => $this->user->id,
            'status'     => 'sent',
            'subtotal'   => 1000.00,
            'vat_amount' => 200.00,
            'total'      => 1200.00,
            'issue_date' => now()->toDateString(),
            'due_date'   => now()->addDays(30)->toDateString(),
        ], $override));
    }

    public function test_html_format_returns_200(): void
    {
        $this->makeInvoice();

        $this->actingAs($this->user)
            ->get(route('reports.invoices.html'))
            ->assertOk();
    }

    public function test_html_shows_invoice_numbers(): void
    {
        $this->makeInvoice(['number' => 'INV-VISIBLE']);

        $this->actingAs($this->user)
            ->get(route('reports.invoices.html'))
            ->assertOk()
            ->assertSee('INV-VISIBLE');
    }

    public function test_pdf_format_returns_pdf_content_type(): void
    {
        $this->makeInvoice();

        $response = $this->actingAs($this->user)
            ->get(route('reports.invoices.pdf'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_xlsx_format_returns_xlsx_content_type(): void
    {
        $this->makeInvoice();

        $response = $this->actingAs($this->user)
            ->get(route('reports.invoices.xlsx'));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }

    public function test_csv_format_returns_csv_content_type(): void
    {
        $this->makeInvoice();

        $response = $this->actingAs($this->user)
            ->get(route('reports.invoices.csv'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_status_filter_scopes_results(): void
    {
        $this->makeInvoice(['number' => 'INV-PAID', 'status' => 'paid']);
        $this->makeInvoice(['number' => 'INV-DRAFT', 'status' => 'draft']);

        $response = $this->actingAs($this->user)
            ->get(route('reports.invoices.html', ['status' => 'paid']));

        $response->assertOk()
            ->assertSee('INV-PAID')
            ->assertDontSee('INV-DRAFT');
    }

    public function test_totals_section_is_present_in_html(): void
    {
        $this->makeInvoice(['status' => 'paid', 'total' => 2400.00]);

        $this->actingAs($this->user)
            ->get(route('reports.invoices.html'))
            ->assertOk()
            ->assertSee('Paid');
    }
}
