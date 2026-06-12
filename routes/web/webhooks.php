<?php

use App\Http\Controllers\PayuWebhookController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Webhooks — CSRF exempt (see bootstrap/app.php validateCsrfTokens)
// -----------------------------------------------------------------------

// Stripe payment webhook
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// Stripe subscription webhook (SaaS plan lifecycle)
Route::post('/stripe/subscription/webhook', [\App\Http\Controllers\Billing\SubscriptionWebhookController::class, 'handle'])
    ->name('stripe.subscription.webhook');

// PayU IPN
Route::post('/payu/notify', [PayuWebhookController::class, 'notify'])->name('payu.notify');

// Domain registrar provider webhooks
Route::post('/webhooks/domain/{provider}', \App\Http\Controllers\Domain\DomainProviderWebhookController::class)
    ->name('webhooks.domain');
