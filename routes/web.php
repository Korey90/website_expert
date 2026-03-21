<?php

use App\Http\Controllers\CalculatorLeadController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\KalkulatorController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

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
        Route::get('/quotes', [PortalController::class, 'quotes'])->name('quotes');
    });

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
