<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Models\Business;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\TaxCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTaxCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private Client $client;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Tax Test Ltd',
            'slug' => 'tax-test',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create();
        $this->client = Client::create([
            'business_id' => $this->business->id,
            'company_name' => 'Customer Ltd',
            'status' => 'active',
        ]);

        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
    }

    public function test_invoice_calculates_mixed_vat_rates_per_item(): void
    {
        $invoice = $this->createInvoice();

        $standardItem = $this->createInvoiceItem($invoice, 'Standard service', 'S20', 100);
        $reducedItem = $this->createInvoiceItem($invoice, 'Reduced service', 'R5', 100);

        $invoice->recalculate();

        $this->assertSame('200.00', $invoice->fresh()->subtotal);
        $this->assertSame('25.00', $invoice->fresh()->vat_amount);
        $this->assertSame('225.00', $invoice->fresh()->total);
        $this->assertSame('20.00', $standardItem->fresh()->vat_rate_snapshot);
        $this->assertSame('20.00', $standardItem->fresh()->vat_amount);
        $this->assertSame('5.00', $reducedItem->fresh()->vat_amount);
    }

    public function test_discount_is_allocated_across_lines_before_vat(): void
    {
        $invoice = $this->createInvoice(['discount_amount' => 10]);

        $standardItem = $this->createInvoiceItem($invoice, 'Standard service', 'S20', 100);
        $reducedItem = $this->createInvoiceItem($invoice, 'Reduced service', 'R5', 100);

        $invoice->recalculate();

        $this->assertSame('95.00', $standardItem->fresh()->net_amount);
        $this->assertSame('19.00', $standardItem->fresh()->vat_amount);
        $this->assertSame('95.00', $reducedItem->fresh()->net_amount);
        $this->assertSame('4.75', $reducedItem->fresh()->vat_amount);
        $this->assertSame('23.75', $invoice->fresh()->vat_amount);
        $this->assertSame('213.75', $invoice->fresh()->total);
    }

    public function test_tax_rate_snapshot_does_not_change_with_tax_code_catalog(): void
    {
        $invoice = $this->createInvoice();
        $item = $this->createInvoiceItem($invoice, 'Historical service', 'S20', 100);

        $invoice->recalculate();
        $this->taxCode('S20')->update(['rate' => 19]);
        $invoice->recalculate();

        $this->assertSame('20.00', $item->fresh()->vat_rate_snapshot);
        $this->assertSame('20.00', $item->fresh()->vat_amount);
        $this->assertSame('120.00', $item->fresh()->gross_amount);
    }

    public function test_quote_uses_the_same_item_level_tax_calculation(): void
    {
        $quote = Quote::create([
            'business_id' => $this->business->id,
            'number' => 'QUOT-TAX-001',
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
            'vat_rate' => 20,
        ]);

        QuoteItem::create([
            'quote_id' => $quote->id,
            'tax_code_id' => $this->taxCode('Z0')->id,
            'description' => 'Zero-rated item',
            'quantity' => 2,
            'unit_price' => 50,
        ]);

        $quote->recalculate();

        $this->assertSame('100.00', $quote->fresh()->subtotal);
        $this->assertSame('0.00', $quote->fresh()->vat_amount);
        $this->assertSame('100.00', $quote->fresh()->total);
        $this->assertSame('0.00', $quote->items()->first()->vat_rate_snapshot);
    }

    public function test_foreign_currency_invoice_snapshots_gbp_amounts_and_tax_point(): void
    {
        $invoice = $this->createInvoice([
            'currency' => 'EUR',
            'tax_point' => '2026-11-02',
            'gbp_exchange_rate' => 0.85,
            'exchange_rate_source' => 'manual',
            'exchange_rate_date' => '2026-11-02',
        ]);
        $item = $this->createInvoiceItem($invoice, 'Euro service', 'S20', 100);

        $invoice->recalculate();
        $invoice->refresh();
        $item->refresh();

        $this->assertSame('85.00', $invoice->subtotal_gbp);
        $this->assertSame('17.00', $invoice->vat_amount_gbp);
        $this->assertSame('102.00', $invoice->total_gbp);
        $this->assertSame('17.00', $item->vat_amount_gbp);
        $this->assertSame('102.00', $item->gross_amount_gbp);
        $this->assertSame('2026-11-02', $item->supply_date?->toDateString());
    }

    public function test_foreign_currency_gbp_values_remain_null_without_an_explicit_rate(): void
    {
        $invoice = $this->createInvoice(['currency' => 'EUR']);
        $item = $this->createInvoiceItem($invoice, 'Unrated Euro service', 'S20', 100);

        $invoice->recalculate();

        $this->assertNull($invoice->fresh()->vat_amount_gbp);
        $this->assertNull($item->fresh()->vat_amount_gbp);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createInvoice(array $overrides = []): Invoice
    {
        return Invoice::create([
            'business_id' => $this->business->id,
            'number' => 'INV-TAX-'.uniqid(),
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
            'vat_rate' => 20,
            'issue_date' => '2026-11-04',
            'due_date' => '2026-12-04',
            ...$overrides,
        ]);
    }

    private function createInvoiceItem(
        Invoice $invoice,
        string $description,
        string $taxCode,
        float $unitPrice,
    ): InvoiceItem {
        return InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'tax_code_id' => $this->taxCode($taxCode)->id,
            'description' => $description,
            'quantity' => 1,
            'unit_price' => $unitPrice,
        ]);
    }

    private function taxCode(string $code): TaxCode
    {
        return TaxCode::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }
}
