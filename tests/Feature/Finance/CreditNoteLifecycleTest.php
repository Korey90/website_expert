<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\AllocateCreditNoteAction;
use App\Actions\Finance\CreateCreditNoteAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\IssueCreditNoteAction;
use App\Actions\Finance\VoidCreditNoteAction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TaxCode;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditNoteLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Invoice $invoice;

    private InvoiceItem $invoiceItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Credits Ltd', 'slug' => 'credits', 'is_active' => true]);
        $this->user = User::factory()->create();
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $client = Client::create([
            'business_id' => $this->business->id,
            'company_name' => 'Customer Ltd',
            'status' => 'active',
        ]);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $taxCode = TaxCode::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', 'S20')
            ->firstOrFail();

        $this->invoice = Invoice::create([
            'business_id' => $this->business->id,
            'number' => 'INV-2026-001',
            'client_id' => $client->id,
            'created_by' => $this->user->id,
            'status' => 'sent',
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-10-31',
            'due_date' => '2026-12-01',
        ]);
        $this->invoiceItem = InvoiceItem::create([
            'business_id' => $this->business->id,
            'invoice_id' => $this->invoice->id,
            'tax_code_id' => $taxCode->id,
            'description' => 'Consulting',
            'quantity' => 2,
            'unit_price' => 50,
        ]);
        $this->invoice->recalculate();
    }

    public function test_it_creates_a_draft_credit_note_from_invoice_snapshots(): void
    {
        $creditNote = $this->createCreditNote(2);

        $this->assertSame(CreditNote::STATUS_DRAFT, $creditNote->status);
        $this->assertSame('CN-2026-001', $creditNote->number);
        $this->assertSame($this->business->id, $creditNote->business_id);
        $this->assertSame('100.00', $creditNote->subtotal);
        $this->assertSame('20.00', $creditNote->vat_amount);
        $this->assertSame('120.00', $creditNote->total);
        $this->assertSame('20.00', $creditNote->vat_amount_gbp);
        $this->assertSame('20.00', $creditNote->items->first()->vat_rate_snapshot);
        $this->assertSame($this->invoiceItem->id, $creditNote->items->first()->invoice_item_id);
    }

    public function test_partial_credit_uses_a_proportion_of_immutable_invoice_amounts(): void
    {
        $creditNote = $this->createCreditNote(0.5);

        $this->assertSame('25.00', $creditNote->subtotal);
        $this->assertSame('5.00', $creditNote->vat_amount);
        $this->assertSame('30.00', $creditNote->total);
    }

    public function test_total_credited_quantity_cannot_exceed_the_invoice_item(): void
    {
        $this->createCreditNote(1.5);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('exceeds');

        $this->createCreditNote(1);
    }

    public function test_credit_note_moves_through_issue_and_allocation_lifecycle(): void
    {
        $creditNote = $this->createCreditNote(2);

        $issued = app(IssueCreditNoteAction::class)->execute($creditNote, $this->user);
        $partial = app(AllocateCreditNoteAction::class)->execute($issued, $this->user, 60);
        $allocated = app(AllocateCreditNoteAction::class)->execute($partial, $this->user, 60);

        $this->assertSame(CreditNote::STATUS_ISSUED, $issued->status);
        $this->assertNotNull($issued->issued_at);
        $this->assertSame(CreditNote::STATUS_PARTIALLY_ALLOCATED, $partial->status);
        $this->assertSame('60.00', $partial->allocated_amount);
        $this->assertSame(CreditNote::STATUS_ALLOCATED, $allocated->status);
        $this->assertSame('120.00', $allocated->allocated_amount);
        $this->assertSame($this->user->id, $allocated->allocated_by);
        $this->assertSame('0.00', $this->invoice->fresh()->amount_due);

        $this->invoice->recalculate();
        $this->assertSame('0.00', $this->invoice->fresh()->amount_due);
    }

    public function test_allocated_credit_note_cannot_be_voided(): void
    {
        $creditNote = app(IssueCreditNoteAction::class)->execute($this->createCreditNote(2), $this->user);
        $creditNote = app(AllocateCreditNoteAction::class)->execute($creditNote, $this->user, 120);

        $this->expectException(DomainException::class);

        app(VoidCreditNoteAction::class)->execute($creditNote, $this->user);
    }

    public function test_draft_credit_note_can_be_voided_without_deleting_its_audit_record(): void
    {
        $creditNote = app(VoidCreditNoteAction::class)->execute($this->createCreditNote(2), $this->user);

        $this->assertSame(CreditNote::STATUS_VOID, $creditNote->status);
        $this->assertSame($this->user->id, $creditNote->voided_by);
        $this->assertNotNull($creditNote->voided_at);
        $this->assertDatabaseHas('credit_notes', ['id' => $creditNote->id, 'deleted_at' => null]);
    }

    public function test_credit_notes_are_isolated_between_tenants(): void
    {
        $ownCreditNote = $this->createCreditNote(2);
        $otherBusiness = Business::create(['name' => 'Other Ltd', 'slug' => 'other', 'is_active' => true]);
        $otherUser = User::factory()->create();
        $otherClient = Client::create([
            'business_id' => $otherBusiness->id,
            'company_name' => 'Other Customer',
            'status' => 'active',
        ]);
        $otherInvoice = Invoice::create([
            'business_id' => $otherBusiness->id,
            'number' => 'INV-2026-001',
            'client_id' => $otherClient->id,
            'created_by' => $otherUser->id,
            'status' => 'sent',
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
            'reason' => 'Other tenant',
            'issue_date' => '2026-11-04',
            'tax_point' => '2026-11-01',
        ]);

        $this->actingAs($this->user);

        $this->assertSame(1, CreditNote::count());
        $this->assertTrue(CreditNote::first()->is($ownCreditNote));
        $this->assertNull(CreditNote::find($otherCreditNote->id));
    }

    private function createCreditNote(float $quantity): CreditNote
    {
        return app(CreateCreditNoteAction::class)->execute($this->invoice, $this->user, [
            'reason' => 'Service adjustment',
            'issue_date' => '2026-11-04',
            'items' => [[
                'invoice_item_id' => $this->invoiceItem->id,
                'quantity' => $quantity,
            ]],
        ]);
    }
}
