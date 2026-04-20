<?php

use App\Http\Controllers\Business\ApiTokenController;
use App\Http\Controllers\Business\BusinessController;
use App\Http\Controllers\Leads\LeadCaptureController;
use App\Http\Controllers\Leads\LeadWebController;
use App\Http\Controllers\LandingPage\AiLandingGeneratorController;
use App\Http\Controllers\Business\BusinessProfileController;
use App\Http\Controllers\LandingPage\LandingPageController;
use App\Http\Controllers\LandingPage\LandingPageSectionController;
use App\Http\Controllers\LandingPage\PublicLandingPageController;
use App\Http\Controllers\Onboarding\OnboardingController;
use App\Http\Controllers\CalculatorLeadController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\KalkulatorController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PayuWebhookController;
use App\Http\Controllers\Portal\ContractController as PortalContractController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\LeadController as PortalLeadController;
use App\Http\Controllers\Portal\InvoiceController as PortalInvoiceController;
use App\Http\Controllers\Portal\NotificationController as PortalNotificationController;
use App\Http\Controllers\Portal\PaymentController as PortalPaymentController;
use App\Http\Controllers\Portal\PaymentResultController as PortalPaymentResultController;
use App\Http\Controllers\Portal\ProjectController as PortalProjectController;
use App\Http\Controllers\Portal\QuoteController as PortalQuoteController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialAccountController;
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
Route::get('/calculate', KalkulatorController::class)->name('kalkulator');

Route::get('/portfolio',         [PortfolioController::class, 'index'])->name('portfolio.index');
Route::get('/portfolio/{slug}',  [PortfolioController::class, 'show'])->name('portfolio.show')
    ->where('slug', '[a-z0-9\-]+');

Route::get('/services',          [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{slug}',   [ServiceController::class, 'show'])->name('services.show')
    ->where('slug', '[a-z0-9\-]+');

// Sitemap XML — public, no auth
Route::get('/sitemap.xml', function () {
    $content = \Illuminate\Support\Facades\Cache::remember('sitemap.xml', 3600, function () {
        $pages     = \App\Models\Page::select('slug', 'updated_at')->get();
        $portfolio = \App\Models\PortfolioProject::select('slug', 'updated_at')
            ->where('is_active', true)->where('is_featured', true)->orderBy('sort_order')->get();
        $services  = \App\Models\ServiceItem::select('slug', 'updated_at')
            ->where('is_active', true)->orderBy('sort_order')->get();

        return view('sitemap', [
            'staticUrls' => [
                ['loc' => url('/'),           'priority' => '1.0', 'changefreq' => 'weekly'],
                ['loc' => url('/calculate'),  'priority' => '0.8', 'changefreq' => 'monthly'],
                ['loc' => url('/portfolio'),  'priority' => '0.8', 'changefreq' => 'weekly'],
                ['loc' => url('/services'),   'priority' => '0.8', 'changefreq' => 'monthly'],
            ],
            'pages'     => $pages,
            'portfolio' => $portfolio,
            'services'  => $services,
        ])->render();
    });

    return response($content, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

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

Route::post('/contact',       [ContactController::class, 'store'])->name('contact.store');
Route::post('/contact/quick', [ContactController::class, 'quickStore'])->name('contact.quick');
Route::post('/calculator-lead', [CalculatorLeadController::class, 'store'])->name('calculator.lead');
Route::post('/leads', [LeadCaptureController::class, 'store'])
    ->name('leads.capture')
    ->middleware('throttle:3,60');

// Stripe webhook — CSRF exempt (see bootstrap/app.php)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

// Stripe subscription webhook (SaaS plan lifecycle) — CSRF exempt
Route::post('/stripe/subscription/webhook', [\App\Http\Controllers\Billing\SubscriptionWebhookController::class, 'handle'])->name('stripe.subscription.webhook');

// PayU IPN — CSRF exempt (see bootstrap/app.php)
Route::post('/payu/notify', [PayuWebhookController::class, 'notify'])->name('payu.notify');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/social/{provider}', [SocialAccountController::class, 'destroy'])->name('profile.social.unlink');
    Route::get('/profile/social/{provider}/connect', [\App\Http\Controllers\Auth\SocialAuthController::class, 'connect'])
        ->name('profile.social.connect')
        ->where('provider', 'google|facebook');

    Route::get('/invoices/{invoice}/pdf', InvoicePdfController::class)->name('invoice.pdf');

    Route::patch('/lead-notes/{note}/unpin', function (\App\Models\LeadNote $note) {
        $note->update(['is_pinned' => false]);
        return back();
    })->name('lead-notes.unpin');

    // Client Portal
    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('/', [PortalDashboardController::class, 'index'])->name('dashboard');

        Route::middleware('portal.client')->group(function () {
            Route::get('/projects', [PortalProjectController::class, 'index'])->name('projects');
            Route::get('/projects/{project}', [PortalProjectController::class, 'show'])->name('projects.show');
            Route::post('/projects/{project}/messages', [PortalProjectController::class, 'storeMessage'])->name('messages.store');

            Route::get('/invoices', [PortalInvoiceController::class, 'index'])->name('invoices');
            Route::get('/invoices/{invoice}', [PortalInvoiceController::class, 'show'])->name('invoices.show');
            Route::get('/invoices/{invoice}/pay', [PortalPaymentController::class, 'selectMethod'])->name('invoices.pay');
            Route::post('/invoices/{invoice}/pay/stripe', [PortalPaymentController::class, 'stripeCheckout'])->name('invoices.pay.stripe');
            Route::post('/invoices/{invoice}/pay/payu', [PortalPaymentController::class, 'payuInitiate'])->name('invoices.pay.payu');
            Route::get('/invoices/{invoice}/payment-result', [PortalPaymentResultController::class, 'show'])->name('invoices.payment-result');

            Route::get('/quotes', [PortalQuoteController::class, 'index'])->name('quotes');
            Route::get('/quotes/{quote}', [PortalQuoteController::class, 'show'])->name('quotes.show');
            Route::post('/quotes/{quote}/accept', [PortalQuoteController::class, 'accept'])->name('quotes.accept');
            Route::post('/quotes/{quote}/reject', [PortalQuoteController::class, 'reject'])->name('quotes.reject');

            Route::get('/contracts', [PortalContractController::class, 'index'])->name('contracts');
            Route::get('/contracts/{contract}', [PortalContractController::class, 'show'])->name('contracts.show');
            Route::post('/contracts/{contract}/sign', [PortalContractController::class, 'sign'])->name('contracts.sign');

            Route::get('/settings/notifications', [PortalNotificationController::class, 'settings'])->name('settings.notifications');
            Route::post('/settings/notifications', [PortalNotificationController::class, 'updateSettings'])->name('settings.notifications.update');
        });

        Route::get('/leads/{lead}', [PortalLeadController::class, 'show'])
            ->middleware('portal.workspace:leads')
            ->name('leads.show');

        Route::middleware('portal.workspace:billing')->group(function () {
            Route::get('/billing', [\App\Http\Controllers\Portal\BillingController::class, 'index'])->name('billing');
            Route::post('/billing/checkout/{plan}', [\App\Http\Controllers\Portal\BillingController::class, 'checkout'])->name('billing.checkout');
            Route::get('/billing/success', [\App\Http\Controllers\Portal\BillingController::class, 'success'])->name('billing.success');
            Route::post('/billing/portal', [\App\Http\Controllers\Portal\BillingController::class, 'portal'])->name('billing.portal');
        });
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

// -----------------------------------------------------------------------
// Onboarding — no has.business middleware (would cause redirect loop)
// -----------------------------------------------------------------------
Route::middleware(['auth', 'verified'])
    ->prefix('onboarding')
    ->name('onboarding.')
    ->group(function () {
        Route::get('/', [OnboardingController::class, 'index'])->name('index');
        Route::get('/profile', [OnboardingController::class, 'profile'])->name('profile');
        Route::post('/profile', [OnboardingController::class, 'saveProfile'])->name('profile.save');
        Route::get('/complete', [OnboardingController::class, 'complete'])->name('complete');
    });

// -----------------------------------------------------------------------
// Business settings & profile
// -----------------------------------------------------------------------
Route::middleware(['auth', 'verified', 'has.business'])->group(function () {
    Route::get('/business/settings', [BusinessController::class, 'edit'])->name('business.edit');
    Route::patch('/business/settings', [BusinessController::class, 'update'])->name('business.update');
    Route::post('/business/logo', [BusinessController::class, 'uploadLogo'])->name('business.logo.upload');
    Route::delete('/business/logo', [BusinessController::class, 'deleteLogo'])->name('business.logo.delete');

    Route::get('/business/profile', [BusinessProfileController::class, 'edit'])->name('business.profile.edit');
    Route::patch('/business/profile', [BusinessProfileController::class, 'update'])->name('business.profile.update');
    Route::get('/business/profile/completion', [BusinessProfileController::class, 'completion'])->name('business.profile.completion');

    // -----------------------------------------------------------------------
    // Leads — Inertia views + lead actions
    // -----------------------------------------------------------------------
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('/{lead}',          [LeadWebController::class, 'show'])->name('show');
        Route::put('/{lead}/assign',   [LeadWebController::class, 'assign'])->name('assign');
        Route::put('/{lead}/stage',    [LeadWebController::class, 'stage'])->name('stage');
        Route::post('/{lead}/won',     [LeadWebController::class, 'won'])->name('won');
        Route::post('/{lead}/lost',    [LeadWebController::class, 'lost'])->name('lost');
    });

    // -----------------------------------------------------------------------
    // API Tokens (for external integrations — Zapier, Make.com)
    // -----------------------------------------------------------------------
    Route::prefix('business/api-tokens')->name('business.api-tokens.')->group(function () {
        Route::get('/',           [ApiTokenController::class, 'index'])->name('index');
        Route::post('/',          [ApiTokenController::class, 'store'])->name('store');
        Route::delete('/{token}', [ApiTokenController::class, 'destroy'])->name('destroy');
    });

    // -----------------------------------------------------------------------
    // Landing Pages (authenticated, has.business) — live under /portal
    // -----------------------------------------------------------------------
    Route::middleware('landing-page.tenant')->prefix('portal/landing-pages')->name('landing-pages.')->group(function () {
        Route::get('/',                                 [LandingPageController::class, 'index'])->name('index');
        Route::get('/create',                           [LandingPageController::class, 'create'])->name('create');
        Route::get('/ai/create',                        [LandingPageController::class, 'createWithAi'])->name('ai.create');
        Route::post('/',                                [LandingPageController::class, 'store'])->name('store');
        Route::get('/{landingPage}',                    [LandingPageController::class, 'show'])->name('show');
        Route::get('/{landingPage}/edit',               [LandingPageController::class, 'edit'])->name('edit');
        Route::patch('/{landingPage}',                  [LandingPageController::class, 'update'])->name('update');
        Route::delete('/{landingPage}',                 [LandingPageController::class, 'destroy'])->name('destroy');
        Route::post('/{landingPage}/publish',           [LandingPageController::class, 'publish'])->name('publish');
        Route::post('/{landingPage}/unpublish',         [LandingPageController::class, 'unpublish'])->name('unpublish');

        Route::prefix('ai')->name('ai.')->group(function () {
            Route::post('/generate', [AiLandingGeneratorController::class, 'generate'])->name('generate');
            Route::post('/variants/{variant}/regenerate-section', [AiLandingGeneratorController::class, 'regenerateSection'])
                ->name('regenerate-section');
            Route::post('/variants/{variant}/save', [AiLandingGeneratorController::class, 'save'])->name('save');
        });

        // Sections
        Route::prefix('/{landingPage}/sections')->name('sections.')->group(function () {
            Route::post('/',                            [LandingPageSectionController::class, 'store'])->name('store');
            Route::patch('/{section}',                  [LandingPageSectionController::class, 'update'])->name('update');
            Route::delete('/{section}',                 [LandingPageSectionController::class, 'destroy'])->name('destroy');
            Route::post('/reorder',                     [LandingPageSectionController::class, 'reorder'])->name('reorder');
        });
    });
});

// -----------------------------------------------------------------------
// Public Landing Pages — no auth required
// -----------------------------------------------------------------------
Route::prefix('lp')->name('lp.')->group(function () {
    Route::get('/{slug}',       [PublicLandingPageController::class, 'show'])->name('show');
    Route::post('/{slug}/submit',[PublicLandingPageController::class, 'submit'])
        ->name('submit')
        ->middleware('throttle:3,60');
});

// -----------------------------------------------------------------------
// Client Briefings — token-based, no auth required
// -----------------------------------------------------------------------
Route::prefix('client/briefings')->name('client.briefings.')->group(function () {
    Route::get('/{token}',        [\App\Http\Controllers\ClientBriefingController::class, 'show'])->name('show');
    Route::patch('/{token}/save', [\App\Http\Controllers\ClientBriefingController::class, 'autosave'])->name('autosave');
    Route::post('/{token}/submit',[\App\Http\Controllers\ClientBriefingController::class, 'submit'])->name('submit')
        ->middleware('throttle:10,60');
});

// -----------------------------------------------------------------------
// Client Sales Offers — token-based, no auth required
// -----------------------------------------------------------------------
Route::prefix('offers')->name('offers.')->group(function () {
    Route::get('/{token}', [\App\Http\Controllers\ClientSalesOfferController::class, 'show'])->name('show');
    Route::post('/{token}/accept', [\App\Http\Controllers\ClientSalesOfferController::class, 'accept'])->name('accept');
});

require __DIR__.'/auth.php';
