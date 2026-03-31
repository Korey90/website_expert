<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalInvoicePayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function makePortalUser(): array
    {
        $user   = User::factory()->create();
        $client = Client::create([
            'company_name'          => 'Pay Test Ltd',
            'primary_contact_name'  => 'Jane Doe',
            'primary_contact_email' => $user->email,
            'portal_user_id'        => $user->id,
        ]);

        return [$user, $client];
    }

    private function makeInvoice(Client $client, User $user, array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'number'     => 'INV-PAY-001',
            'client_id'  => $client->id,
            'created_by' => $user->id,
            'status'     => 'sent',
            'currency'   => 'GBP',
            'subtotal'   => 1000,
            'vat_amount' => 200,
            'total'      => 1200,
            'amount_due' => 1200,
            'issue_date' => now()->toDateString(),
            'due_date'   => now()->addDays(14)->toDateString(),
        ], $overrides));
    }

    // -----------------------------------------------------------------------
    // Access control
    // -----------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_view_pay_page(): void
    {
        [$user, $client] = $this->makePortalUser();
        $invoice = $this->makeInvoice($client, $user);

        $this->get(route('portal.invoices.pay', $invoice))
            ->assertRedirect(route('login'));
    }

    public function test_client_cannot_pay_another_clients_invoice(): void
    {
        [$user, $client]       = $this->makePortalUser();
        [$user2, $client2]     = $this->makePortalUser();

        // Invoice belongs to client2 but user (client) tries to access
        $invoice = Invoice::create([
            'number'     => 'INV-OTHER-001',
            'client_id'  => $client2->id,
            'created_by' => $user2->id,
            'status'     => 'sent',
            'currency'   => 'GBP',
            'subtotal'   => 500,
            'vat_amount' => 100,
            'total'      => 600,
            'amount_due' => 600,
            'issue_date' => now()->toDateString(),
            'due_date'   => now()->addDays(14)->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('portal.invoices.pay', $invoice))
            ->assertForbidden();
    }

    public function test_pay_page_returns_200_for_sent_invoice(): void
    {
        [$user, $client] = $this->makePortalUser();
        $invoice = $this->makeInvoice($client, $user);

        // Settings table may be empty — selectMethod reads stripe/payu enabled flags
        $this->actingAs($user)
            ->get(route('portal.invoices.pay', $invoice))
            ->assertOk();
    }

    public function test_draft_invoice_cannot_be_paid(): void
    {
        [$user, $client] = $this->makePortalUser();
        $invoice = $this->makeInvoice($client, $user, ['status' => 'draft']);

        $this->actingAs($user)
            ->get(route('portal.invoices.pay', $invoice))
            ->assertRedirect(route('portal.invoices.show', $invoice));
    }

    public function test_paid_invoice_cannot_be_paid_again(): void
    {
        [$user, $client] = $this->makePortalUser();
        $invoice = $this->makeInvoice($client, $user, ['status' => 'paid']);

        $this->actingAs($user)
            ->get(route('portal.invoices.pay', $invoice))
            ->assertRedirect(route('portal.invoices.show', $invoice));
    }

    // -----------------------------------------------------------------------
    // Stripe checkout
    // -----------------------------------------------------------------------

    public function test_stripe_checkout_aborts_when_stripe_disabled(): void
    {
        [$user, $client] = $this->makePortalUser();
        $invoice = $this->makeInvoice($client, $user);

        // No settings row → stripe disabled by default
        $this->actingAs($user)
            ->post(route('portal.invoices.pay.stripe', $invoice))
            ->assertStatus(503);
    }

    public function test_stripe_checkout_rejects_paid_invoice(): void
    {
        [$user, $client] = $this->makePortalUser();
        $invoice = $this->makeInvoice($client, $user, ['status' => 'paid']);

        $this->actingAs($user)
            ->post(route('portal.invoices.pay.stripe', $invoice))
            ->assertStatus(422);
    }

    // -----------------------------------------------------------------------
    // PayU initiation
    // -----------------------------------------------------------------------

    public function test_payu_initiate_aborts_when_payu_disabled(): void
    {
        [$user, $client] = $this->makePortalUser();
        $invoice = $this->makeInvoice($client, $user);

        // No settings row → payu disabled by default
        $this->actingAs($user)
            ->post(route('portal.invoices.pay.payu', $invoice))
            ->assertStatus(503);
    }

    public function test_payu_initiate_rejects_draft_invoice(): void
    {
        [$user, $client] = $this->makePortalUser();
        $invoice = $this->makeInvoice($client, $user, ['status' => 'draft']);

        $this->actingAs($user)
            ->post(route('portal.invoices.pay.payu', $invoice))
            ->assertStatus(422);
    }

    // -----------------------------------------------------------------------
    // Payment result page
    // -----------------------------------------------------------------------

    public function test_payment_result_page_is_accessible(): void
    {
        [$user, $client] = $this->makePortalUser();
        $invoice = $this->makeInvoice($client, $user);

        $this->actingAs($user)
            ->get(route('portal.invoices.payment-result', $invoice) . '?payment=success')
            ->assertOk();
    }

    public function test_payment_result_page_with_cancelled_status(): void
    {
        [$user, $client] = $this->makePortalUser();
        $invoice = $this->makeInvoice($client, $user);

        $this->actingAs($user)
            ->get(route('portal.invoices.payment-result', $invoice) . '?payment=cancelled')
            ->assertOk();
    }
}
