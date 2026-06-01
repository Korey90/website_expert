<?php

namespace App\Actions\Domain;

use App\Models\DomainOrder;
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
        $stripeSecret = config('services.stripe.secret', '');

        if (empty($stripeSecret)) {
            throw new \RuntimeException('Stripe payments are not configured.');
        }

        Stripe::setApiKey($stripeSecret);

        $amount   = (int) round((float) $order->retail_price * 100);
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
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
        ]);
    }
}
