<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Handles Stripe webhooks for SaaS subscription lifecycle.
 * Separate from StripeWebhookController (which handles CRM invoice payments).
 *
 * CSRF exempt — configured in bootstrap/app.php or web.php
 */
class SubscriptionWebhookController extends Controller
{
    /** Stripe Price ID → plan name mapping (from config/services.php) */
    private function priceToPlан(string $priceId): ?string
    {
        return match ($priceId) {
            config('services.stripe.price_pro_monthly')    => 'pro',
            config('services.stripe.price_agency_monthly') => 'agency',
            default                                         => null,
        };
    }

    public function handle(Request $request): Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.subscription_webhook_secret', '');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Subscription webhook sig failed: ' . $e->getMessage());
            return response('Invalid signature', 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Subscription webhook invalid payload: ' . $e->getMessage());
            return response('Invalid payload', 400);
        }

        match ($event->type) {
            'checkout.session.completed'         => $this->handleCheckoutCompleted($event->data->object),
            'customer.subscription.updated'      => $this->handleSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted'      => $this->handleSubscriptionCancelled($event->data->object),
            default => Log::info('Subscription webhook unhandled: ' . $event->type),
        };

        return response('OK', 200);
    }

    private function handleCheckoutCompleted(\Stripe\Checkout\Session $session): void
    {
        $businessId = $session->metadata->business_id ?? null;
        $plan       = $session->metadata->plan ?? null;

        if (! $businessId || ! $plan) {
            return;
        }

        $business = Business::find($businessId);
        if (! $business) {
            return;
        }

        $business->update([
            'plan'               => $plan,
            'stripe_customer_id' => $session->customer,
            'trial_ends_at'      => null, // Trial ended — now on paid plan
        ]);

        Log::info("Subscription: Business {$businessId} upgraded to {$plan}");
    }

    private function handleSubscriptionUpdated(\Stripe\Subscription $subscription): void
    {
        $business = Business::where('stripe_customer_id', $subscription->customer)->first();
        if (! $business) {
            return;
        }

        $priceId = $subscription->items->data[0]?->price?->id ?? null;
        $plan    = $priceId ? $this->priceToPlан($priceId) : null;

        if ($plan && $subscription->status === 'active') {
            $business->update(['plan' => $plan]);
        } elseif (in_array($subscription->status, ['canceled', 'unpaid', 'past_due'])) {
            $business->update(['plan' => 'free']);
        }

        Log::info("Subscription updated: Business {$business->id} → {$plan} ({$subscription->status})");
    }

    private function handleSubscriptionCancelled(\Stripe\Subscription $subscription): void
    {
        $business = Business::where('stripe_customer_id', $subscription->customer)->first();
        if (! $business) {
            return;
        }

        $business->update(['plan' => 'free']);

        Log::info("Subscription cancelled: Business {$business->id} reverted to free");
    }
}
