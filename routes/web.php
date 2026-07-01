<?php

use App\Http\Controllers\ServicePageController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Route orchestrator
//
// Each file is self-contained with its own imports.
// Order matters: catch-all must be registered last.
// -----------------------------------------------------------------------

// Auth routes first — must beat any /{slug} catch-all
require __DIR__.'/auth.php';

require __DIR__.'/web/portal.php';

require __DIR__.'/web/notifications.php';
require __DIR__.'/web/contact.php';
require __DIR__.'/web/public.php';
require __DIR__.'/web/webhooks.php';
require __DIR__.'/web/domains.php';
require __DIR__.'/web/profile.php';
require __DIR__.'/web/onboarding.php';
require __DIR__.'/web/business.php';
require __DIR__.'/web/calendar.php';
require __DIR__.'/web/admin.php';

// Dashboard redirect — required by RedirectIfAuthenticated (guest middleware)
Route::redirect('/dashboard', '/admin')->name('dashboard');

// -----------------------------------------------------------------------
// Service Pages catch-all — must be registered LAST.
// Matches /{slug} only if no other route matched first.
// -----------------------------------------------------------------------
Route::get('/{slug}', [ServicePageController::class, 'show'])
    ->name('service-page.show')
    ->where('slug', '[a-z0-9\-]+');

