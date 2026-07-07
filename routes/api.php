<?php

use App\Http\Controllers\Api\LeadCaptureController;
use App\Http\Controllers\Api\SecurityWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are prefixed with /api automatically by bootstrap/app.php.
| The 'api' middleware group is applied by default.
|
*/

// -----------------------------------------------------------------------
// v1 — External Lead Capture API
// Requires: Authorization: Bearer {api_token}
// -----------------------------------------------------------------------
Route::prefix('v1')
    ->middleware('api.token')
    ->group(function () {
        Route::post('/leads', [LeadCaptureController::class, 'store'])
            ->name('api.leads.store')
            ->middleware('throttle:60,1');
    });

// -----------------------------------------------------------------------
// Security Webhook — fail2ban events
// Requires: X-Security-Secret header
// -----------------------------------------------------------------------
Route::post('/security/webhook', SecurityWebhookController::class)
    ->name('api.security.webhook')
    ->middleware('throttle:120,1');
