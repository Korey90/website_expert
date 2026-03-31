<?php

namespace App\Http\Controllers\Portal;

use App\Models\Invoice;
use App\Models\Setting;
use App\Services\PayuService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends BasePortalController
{
    public function selectMethod(Invoice $invoice): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $invoice->client_id !== $client->id) {
            abort(403);
        }

        if (in_array($invoice->status, ['draft', 'cancelled', 'paid'])) {
            return redirect()->route('portal.invoices.show', $invoice)
                ->with('error', 'This invoice cannot be paid online.');
        }

        return Inertia::render('Portal/PayInvoice', [
            'invoice'       => $invoice->load('items'),
            'client'        => $client->only('id', 'company_name'),
            'stripeEnabled' => (bool) Setting::get('stripe_enabled', false),
            'payuEnabled'   => (bool) Setting::get('payu_enabled', false),
            'stripePk'      => Setting::get('stripe_pk', ''),
        ]);
    }

    public function stripeCheckout(Invoice $invoice): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $invoice->client_id !== $client->id) {
            abort(403);
        }

        if (in_array($invoice->status, ['draft', 'cancelled', 'paid'])) {
            abort(422, 'Invoice cannot be paid.');
        }

        $sk = Setting::get('stripe_sk', config('services.stripe.secret', ''));
        abort_if(empty($sk) || ! Setting::get('stripe_enabled'), 503, 'Stripe payments are not enabled.');

        \Stripe\Stripe::setApiKey($sk);

        $amountDue = (int) round(($invoice->amount_due ?? 0) * 100);
        $currency  = strtolower($invoice->currency ?? Setting::get('payment_currency', 'GBP'));

        $session = \Stripe\Checkout\Session::create([
            'mode'           => 'payment',
            'customer_email' => $invoice->client?->primary_contact_email,
            'line_items'     => [[
                'price_data' => [
                    'currency'     => $currency,
                    'product_data' => ['name' => 'Invoice ' . $invoice->number],
                    'unit_amount'  => $amountDue,
                ],
                'quantity' => 1,
            ]],
            'metadata'    => [
                'invoice_id' => $invoice->id,
                'client_id'  => $invoice->client_id,
            ],
            'success_url' => route('portal.invoices.payment-result', $invoice) . '?payment=success',
            'cancel_url'  => route('portal.invoices.payment-result', $invoice) . '?payment=cancelled',
        ]);

        return redirect($session->url);
    }

    public function payuInitiate(Invoice $invoice): RedirectResponse
    {
        $client = $this->clientForUser();

        if (! $client || $invoice->client_id !== $client->id) {
            abort(403);
        }

        if (in_array($invoice->status, ['draft', 'cancelled', 'paid'])) {
            abort(422, 'Invoice cannot be paid.');
        }

        abort_if(! Setting::get('payu_enabled'), 503, 'PayU payments are not enabled.');

        $payu = new PayuService();

        $returnUrl = route('portal.invoices.payment-result', $invoice) . '?payment=success';
        $notifyUrl = route('payu.notify');

        $result = $payu->createOrder($invoice, $client, $returnUrl, $notifyUrl);

        return redirect($result['redirectUri']);
    }
}
