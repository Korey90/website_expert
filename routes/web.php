<?php

use App\Http\Controllers\CalculatorLeadController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\KalkulatorController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PayuWebhookController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmailTemplatePreviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Notification follow-through: marks most-recent unread notification as read, then redirects.
// The URL redirect is intentionally limited to relative paths to prevent open-redirect attacks.
Route::get('/notification-follow', function (Request $request) {
    $to = $request->query('to', '/admin');
    if (! preg_match('#^/[^/]#', $to)) {
        $to = '/admin';
    }
    $id = $request->query('id');
    if ($id && preg_match('/^[0-9a-f\-]{36}$/i', $id)) {
        auth()->user()?->notifications()->where('id', $id)->update(['read_at' => now()]);
    } else {
        auth()->user()?->unreadNotifications()->latest()->first()?->markAsRead();
    }
    return redirect($to);
})->middleware('auth')->name('notification.follow');

// JS keepalive fetch endpoint — marks a specific notification as read.
Route::post('/notification-mark-read', function (Request $request) {
    $id = $request->input('id', '');
    if ($id && preg_match('/^[0-9a-f\-]{36}$/i', $id)) {
        auth()->user()?->notifications()->where('id', $id)->update(['read_at' => now()]);
    }
    return response()->noContent();
})->middleware('auth')->name('notification.mark-read');

Route::get('/', WelcomeController::class)->name('home');
Route::get('/kalkulator', KalkulatorController::class)->name('kalkulator');
Route::get('/p/{slug}', [PageController::class, 'show'])->name('page.show');
Route::get('/{slug}', [PageController::class, 'show'])->name('page.show.clean')
    ->where('slug', 'privacy-policy|terms-and-conditions|cookies|accessibility');

Route::get('/lang/{locale}', function (string $locale) {
    $supported = array_keys(config('languages'));
    if (in_array($locale, $supported)) {
        session(['locale' => $locale]);
    }
    return redirect()->back(302, [], route('home'));
})->where('locale', '[a-z]{2}')->name('lang.switch');

Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::post('/calculator-lead', [CalculatorLeadController::class, 'store'])->name('calculator.lead');

// Stripe webhook — CSRF exempt (see bootstrap/app.php)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// PayU IPN — CSRF exempt (see bootstrap/app.php)
Route::post('/payu/notify', [PayuWebhookController::class, 'notify'])->name('payu.notify');

Route::get('/dashboard', function () {
    return inertia('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/invoices/{invoice}/pdf', InvoicePdfController::class)->name('invoice.pdf');

    Route::patch('/lead-notes/{note}/unpin', function (\App\Models\LeadNote $note) {
        $note->update(['is_pinned' => false]);
        return back();
    })->name('lead-notes.unpin');

    // Client Portal
    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/projects', [PortalController::class, 'projects'])->name('projects');
        Route::get('/projects/{project}', [PortalController::class, 'project'])->name('project');
        Route::post('/projects/{project}/messages', [PortalController::class, 'postMessage'])->name('messages.store');
        Route::get('/invoices', [PortalController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoice}', [PortalController::class, 'invoice'])->name('invoice');
        Route::get('/invoices/{invoice}/pay', [PortalController::class, 'selectPaymentMethod'])->name('invoices.pay');
        Route::post('/invoices/{invoice}/pay/stripe', [PortalController::class, 'stripeCheckout'])->name('invoices.pay.stripe');
        Route::post('/invoices/{invoice}/pay/payu', [PortalController::class, 'payuInitiate'])->name('invoices.pay.payu');
        Route::get('/quotes', [PortalController::class, 'quotes'])->name('quotes');
        Route::get('/quotes/{quote}', [PortalController::class, 'quote'])->name('quote');
        Route::post('/quotes/{quote}/accept', [PortalController::class, 'acceptQuote'])->name('quotes.accept');
        Route::post('/quotes/{quote}/reject', [PortalController::class, 'rejectQuote'])->name('quotes.reject');
        Route::get('/contracts', [PortalController::class, 'contracts'])->name('contracts');
        Route::get('/contracts/{contract}', [PortalController::class, 'contract'])->name('contract');
        Route::post('/contracts/{contract}/sign', [PortalController::class, 'signContract'])->name('contracts.sign');

        // Communication preferences
        Route::get('/settings/notifications', [PortalController::class, 'notificationSettings'])->name('settings.notifications');
        Route::post('/settings/notifications', [PortalController::class, 'updateNotificationSettings'])->name('settings.notifications.update');
    });

    // Email template preview (admin only)
    Route::get('/admin/email-preview/{template}/{locale?}', [EmailTemplatePreviewController::class, 'show'])
        ->name('admin.email-preview');

    // Reports — admin only
    Route::prefix('reports')->name('reports.')->group(function () {
        foreach (['html', 'pdf', 'xlsx', 'csv'] as $format) {
            Route::get("leads/{$format}",    [ReportController::class, 'leads'])->defaults('format', $format)->name("leads.{$format}");
            Route::get("invoices/{$format}", [ReportController::class, 'invoices'])->defaults('format', $format)->name("invoices.{$format}");
            Route::get("projects/{$format}", [ReportController::class, 'projects'])->defaults('format', $format)->name("projects.{$format}");
        }
    });
});

require __DIR__.'/auth.php';
