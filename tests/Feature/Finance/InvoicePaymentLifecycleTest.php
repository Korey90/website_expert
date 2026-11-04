<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Models\Business;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\TaxCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePaymentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Payment Test Ltd',
            'slug' => 'payment-test',
            'is_active' => true,
        ]);
        $user = User::factory()->create();
        $client = Client::create([
            'business_id' => $this->business->id,
            'company_name' => 'Payment Customer Ltd',
            'status' => 'active',
        ]);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $user);
        $this->invoice = Invoice::create([
            'business_id' => $this->business->id,
            'number' => 'INV-PAY-001',
            'client_id' => $client->id,
            'created_by' => $user->id,
            'status' => 'sent',
            'currency' => 'GBP',
            'issue_date' => '2026-11-04',
            'due_date' => '2026-12-04',
        ]);
        InvoiceItem::create([
            'business_id' => $this->business->id,
            'invoice_id' => $this->invoice->id,
            'tax_code_id' => TaxCode::withoutGlobalScopes()
                ->where('business_id', $this->business->id)
                ->where('code', 'S20')
                ->value('id'),
            'description' => 'Consulting',
            'quantity' => 1,
            'unit_price' => 100,
        ]);
        $this->invoice->recalculate();
    }

    public function test_completed_payments_synchronize_partial_and_paid_invoice_statuses(): void
    {
        $firstPayment = $this->payment(40, 'completed', 'PAY-001');

        $this->invoice->refresh();
        $this->assertSame('partially_paid', $this->invoice->status);
        $this->assertSame('40.00', $this->invoice->amount_paid);
        $this->assertSame('80.00', $this->invoice->amount_due);
        $this->assertNull($this->invoice->paid_at);

        $secondPayment = $this->payment(80, 'completed', 'PAY-002');

        $this->invoice->refresh();
        $this->assertSame('paid', $this->invoice->status);
        $this->assertSame('120.00', $this->invoice->amount_paid);
        $this->assertSame('0.00', $this->invoice->amount_due);
        $this->assertNotNull($this->invoice->paid_at);

        $secondPayment->delete();

        $this->invoice->refresh();
        $this->assertSame('partially_paid', $this->invoice->status);
        $this->assertSame('40.00', $this->invoice->amount_paid);
        $this->assertSame('80.00', $this->invoice->amount_due);
        $this->assertNull($this->invoice->paid_at);
        $this->assertDatabaseHas('payments', ['id' => $firstPayment->id]);
    }

    public function test_pending_and_failed_payments_do_not_reduce_the_invoice_balance(): void
    {
        $this->payment(50, 'pending', 'PAY-PENDING');
        $this->payment(50, 'failed', 'PAY-FAILED');

        $this->invoice->refresh();
        $this->assertSame('sent', $this->invoice->status);
        $this->assertSame('0.00', $this->invoice->amount_paid);
        $this->assertSame('120.00', $this->invoice->amount_due);
    }

    private function payment(float $amount, string $status, string $reference): Payment
    {
        return Payment::create([
            'business_id' => $this->business->id,
            'invoice_id' => $this->invoice->id,
            'amount' => $amount,
            'currency' => 'GBP',
            'method' => 'bank_transfer',
            'status' => $status,
            'reference' => $reference,
            'paid_at' => $status === 'completed' ? now() : null,
        ]);
    }
}
