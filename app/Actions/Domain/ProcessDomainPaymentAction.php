<?php

namespace App\Actions\Domain;

use App\Models\DomainOrder;
use App\Models\Setting;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class ProcessDomainPaymentAction
{
    /**
     * Create a Stripe Checkout Session for a domain order.
     *
     * @throws \RuntimeException  when Stripe is not configured
     * @throws ApiErrorException  when Stripe API call fails
     */
    public function execute(DomainOrder $order, string $successUrl, string $cancelUrl): StripeSession
    {
        $stripeSecret = config('services.stripe.secret') ?: Setting::get('stripe_sk', '');

        if (empty($stripeSecret)) {
            throw new \RuntimeException('Stripe payments are not configured.');
        }

        Stripe::setApiKey($stripeSecret);

        // retail_price is the net (ex-VAT) price; charge the VAT-inclusive gross amount
        $vatRate  = 20.0;
        $netPrice = (float) $order->retail_price;
        $gross    = round($netPrice * (1 + $vatRate / 100), 2);
        $amount   = (int) round($gross * 100);
        $currency = strtolower($order->currency ?? 'gbp');
        $email    = auth()->user()?->email ?? '';
        $label    = ucfirst($order->action) . ' ' . $order->full_domain
            . ' (' . $order->years . ' ' . ($order->years === 1 ? 'year' : 'years') . ')';

        return StripeSession::create([
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
            ],
            'success_url' => $successUrl . (str_contains($successUrl, '?') ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $cancelUrl,
        ]);
    }
}
