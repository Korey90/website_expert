<?php

namespace App\Http\Controllers\Portal;

use App\Models\DomainOrder;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class DomainCheckoutController extends BasePortalController
{
    /**
     * GET /portal/domains/order/{order}/checkout — show order summary before payment.
     */
    public function show(DomainOrder $order): Response|RedirectResponse
    {
        $client = $this->clientForUser();

        if ($order->client_id && $client && $order->client_id !== $client->id) {
            abort(403);
        }

        if ($order->status === 'completed') {
            return redirect()
                ->route('portal.domains.result', $order->id)
                ->with('payment', 'success');
        }

        $netPrice  = (float) $order->retail_price;
        $vatRate   = 20.0;
        $vatAmount = round($netPrice * $vatRate / 100, 2);
        $total     = round($netPrice + $vatAmount, 2);

        return Inertia::render('Portal/Domains/Checkout', [
            'client' => $client?->only('id', 'company_name'),
            'order'  => [
                'id'           => $order->id,
                'full_domain'  => $order->full_domain,
                'action'       => $order->action,
                'years'        => $order->years,
                'retail_price' => $netPrice,
                'vat_rate'     => $vatRate,
                'vat_amount'   => $vatAmount,
                'total'        => $total,
                'currency'     => $order->currency,
                'status'       => $order->status,
            ],
        ]);
    }

    /**
     * POST /portal/domains/order/{order}/checkout — create Stripe Checkout Session and redirect.
     */
    public function pay(Request $request, DomainOrder $order): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $client = $this->clientForUser();

        if ($order->client_id && $client && $order->client_id !== $client->id) {
            abort(403);
        }

        if ($order->status !== 'pending_payment') {
            return back()->withErrors(['order' => 'This order cannot be paid at this time.']);
        }

        $stripeSecret = Setting::get('stripe_sk', config('services.stripe.secret', ''));
        abort_if(empty($stripeSecret) || ! Setting::get('stripe_enabled'), 503, 'Stripe payments are not configured.');

        try {
            Stripe::setApiKey($stripeSecret);

            // retail_price is net (ex-VAT); charge the VAT-inclusive gross amount
            $netPrice = (float) $order->retail_price;
            $gross    = round($netPrice * 1.2, 2);
            $amount   = (int) round($gross * 100);
            $currency = strtolower($order->currency ?? 'gbp');
            $email    = $client?->primary_contact_email ?? auth()->user()->email;
            $label    = ucfirst($order->action) . ' ' . $order->full_domain
                . ' (' . $order->years . ' ' . ($order->years === 1 ? 'year' : 'years') . ')';
            unset($netPrice, $gross);

            $session = StripeSession::create([
                'mode'           => 'payment',
                'customer_email' => $email,
                'line_items'     => [[
                    'price_data' => [
                        'currency'     => $currency,
                        'product_data' => ['name' => $label],
                        'unit_amount'  => $amount,
                    ],
                    'quantity' => 1,
                ]],
                'metadata' => [
                    'domain_order_id' => $order->id,
                    'client_id'       => $order->client_id ?? '',
                ],
                'success_url' => route('portal.domains.result', $order->id) . '?payment=success',
                'cancel_url'  => route('portal.domains.result', $order->id) . '?payment=cancelled',
            ]);

            return Inertia::location($session->url);
        } catch (ApiErrorException $e) {
            Log::error('Stripe domain checkout error: ' . $e->getMessage(), ['order_id' => $order->id]);
            return back()->withErrors(['stripe' => 'Payment provider error. Please try again.']);
        }
    }

    /**
     * GET /portal/domains/order/{order}/result?payment=success|cancelled
     * Result page shown after Stripe redirect.
     */
    public function result(Request $request, DomainOrder $order): Response
    {
        $client  = $this->clientForUser();
        $payment = $request->input('payment', '');

        return Inertia::render('Portal/Domains/Result', [
            'client'  => $client?->only('id', 'company_name'),
            'order'   => [
                'id'           => $order->id,
                'full_domain'  => $order->full_domain,
                'action'       => $order->action,
                'years'        => $order->years,
                'retail_price' => (float) $order->retail_price,
                'currency'     => $order->currency,
                'status'       => $order->fresh()->status,
            ],
            'payment' => $payment,
        ]);
    }
}
