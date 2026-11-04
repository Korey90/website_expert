<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateCreditNoteAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Filament\Resources\CreditNoteResource;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TaxCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FinancialDocumentPdfTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Invoice $invoice;

    private CreditNote $creditNote;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Fallback Ltd', 'slug' => 'pdf-business', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $client = Client::create([
            'business_id' => $this->business->id,
            'company_name' => 'UK Customer Ltd',
            'status' => 'active',
            'address_line1' => '20 Market Street',
            'city' => 'Manchester',
            'postcode' => 'M1 1AA',
            'country' => 'GB',
            'vat_number' => 'GB987654321',
        ]);
        $setup = app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $setup['profile']->update([
            'entity_type' => 'limited_company',
            'legal_name' => 'Compliant Seller Ltd',
            'company_number' => '12345678',
            'registered_address' => [
                'line_1' => '1 High Street',
                'city' => 'London',
                'postcode' => 'SW1A 1AA',
                'country' => 'GB',
            ],
            'vat_registered' => true,
            'vat_number' => 'GB123456789',
        ]);
        $taxCode = TaxCode::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', 'S20')
            ->firstOrFail();

        $this->invoice = Invoice::create([
            'business_id' => $this->business->id,
            'number' => 'INV-2026-900',
            'client_id' => $client->id,
            'created_by' => $this->user->id,
            'status' => 'sent',
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-10-31',
            'due_date' => '2026-12-01',
        ]);
        $item = InvoiceItem::create([
            'business_id' => $this->business->id,
            'invoice_id' => $this->invoice->id,
            'tax_code_id' => $taxCode->id,
            'description' => 'UK consulting service',
            'quantity' => 2,
            'unit_price' => 100,
        ]);
        $this->invoice->recalculate();
        $this->creditNote = app(CreateCreditNoteAction::class)->execute($this->invoice, $this->user, [
            'reason' => 'Agreed service adjustment',
            'issue_date' => '2026-11-04',
            'tax_point' => '2026-10-31',
            'items' => [['invoice_item_id' => $item->id, 'quantity' => 1]],
        ]);
    }

    public function test_invoice_document_contains_uk_seller_and_vat_details(): void
    {
        $invoice = $this->invoice->fresh()->load([
            'business.accountingProfile', 'client', 'project', 'items.taxCode', 'createdBy',
        ]);

        $html = view('pdf.invoice', compact('invoice'))->render();

        $this->assertStringContainsString('Compliant Seller Ltd', $html);
        $this->assertStringContainsString('Company no: 12345678', $html);
        $this->assertStringContainsString('VAT registration no: GB123456789', $html);
        $this->assertStringContainsString('VAT breakdown', $html);
        $this->assertStringContainsString('S20', $html);
        $this->assertStringContainsString('31 Oct 2026', $html);
    }

    public function test_credit_note_document_references_original_invoice_and_vat_details(): void
    {
        $creditNote = $this->creditNote->fresh()->load([
            'business.accountingProfile', 'client', 'invoice', 'items.taxCode', 'createdBy', 'issuedBy',
        ]);

        $html = view('pdf.credit-note', compact('creditNote'))->render();

        $this->assertStringContainsString('Credit Note', $html);
        $this->assertStringContainsString('INV-2026-900', $html);
        $this->assertStringContainsString('Agreed service adjustment', $html);
        $this->assertStringContainsString('Compliant Seller Ltd', $html);
        $this->assertStringContainsString('VAT breakdown', $html);
        $this->assertStringContainsString('S20', $html);
    }

    public function test_authenticated_user_can_download_financial_document_pdfs(): void
    {
        $this->actingAs($this->user);

        $this->get(route('invoice.pdf', $this->invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('credit-note.pdf', $this->creditNote))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_authorized_user_can_open_credit_note_resource_pages(): void
    {
        foreach (['access_admin_panel', 'view_invoices', 'edit_invoices'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_invoices', 'edit_invoices']);

        $this->actingAs($this->user)
            ->get(CreditNoteResource::getUrl('index'))
            ->assertOk()
            ->assertSee($this->creditNote->number);

        $this->get(CreditNoteResource::getUrl('view', ['record' => $this->creditNote]))
            ->assertOk()
            ->assertSee($this->creditNote->number)
            ->assertSee('Agreed service adjustment');
    }

    public function test_credit_note_pdf_route_does_not_expose_another_tenant(): void
    {
        $otherBusiness = Business::create(['name' => 'Other Ltd', 'slug' => 'other-pdf', 'is_active' => true]);
        $otherUser = User::factory()->create();
        $otherClient = Client::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'company_name' => 'Other Customer',
            'status' => 'active',
        ]);
        $otherInvoice = Invoice::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'number' => 'INV-2026-001',
            'client_id' => $otherClient->id,
            'created_by' => $otherUser->id,
            'status' => 'sent',
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'due_date' => '2026-12-01',
        ]);
        $otherCreditNote = CreditNote::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'number' => 'CN-2026-001',
            'invoice_id' => $otherInvoice->id,
            'client_id' => $otherClient->id,
            'created_by' => $otherUser->id,
            'status' => CreditNote::STATUS_DRAFT,
            'currency' => 'GBP',
            'reason' => 'Private adjustment',
            'issue_date' => '2026-11-04',
            'tax_point' => '2026-11-01',
        ]);

        $this->actingAs($this->user)
            ->get(route('credit-note.pdf', $otherCreditNote))
            ->assertNotFound();
    }
}
