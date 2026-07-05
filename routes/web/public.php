<?php

use App\Http\Controllers\KalkulatorController;
use App\Http\Controllers\LandingPage\PublicLandingPageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Public pages — no auth required
// -----------------------------------------------------------------------

Route::get('/', WelcomeController::class)->name('home');
Route::get('/calculate', KalkulatorController::class)->name('kalkulator');

Route::get('/portfolio',        [PortfolioController::class, 'index'])->name('portfolio.index');
Route::get('/portfolio/{slug}', [PortfolioController::class, 'show'])->name('portfolio.show')
    ->where('slug', '[a-z0-9\-]+');

Route::get('/services',         [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{slug}',  [ServiceController::class, 'show'])->name('services.show')
    ->where('slug', '[a-z0-9\-]+');

// Sitemap XML
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

// Explicit routes for legal/CMS pages — avoids conflict with the /{slug} catch-all in web.php
Route::get('/privacy-policy',       [PageController::class, 'show'])->defaults('slug', 'privacy-policy')->name('page.privacy_policy');
Route::get('/terms-and-conditions', [PageController::class, 'show'])->defaults('slug', 'terms-and-conditions')->name('page.terms_and_conditions');
Route::get('/cookies',              [PageController::class, 'show'])->defaults('slug', 'cookies')->name('page.cookies');
Route::get('/accessibility',        [PageController::class, 'show'])->defaults('slug', 'accessibility')->name('page.accessibility');

Route::get('/lang/{locale}', function (string $locale) {
    $supported = array_keys(config('languages'));
    if (in_array($locale, $supported)) {
        session(['locale' => $locale]);
    }
    return redirect()->back(302, [], route('home'));
})->where('locale', '[a-z]{2}')->name('lang.switch');

// -----------------------------------------------------------------------
// Public Landing Pages
// -----------------------------------------------------------------------
Route::prefix('lp')->name('lp.')->group(function () {
    Route::get('/{slug}',        [PublicLandingPageController::class, 'show'])->name('show');
    Route::post('/{slug}/submit', [PublicLandingPageController::class, 'submit'])
        ->name('submit')
        ->middleware('throttle:3,60');
});

// -----------------------------------------------------------------------
// Client Briefings — token-based, no auth required
// -----------------------------------------------------------------------
Route::prefix('client/briefings')->name('client.briefings.')->group(function () {
    Route::get('/{token}',         [\App\Http\Controllers\ClientBriefingController::class, 'show'])->name('show');
    Route::patch('/{token}/save',  [\App\Http\Controllers\ClientBriefingController::class, 'autosave'])->name('autosave');
    Route::post('/{token}/submit', [\App\Http\Controllers\ClientBriefingController::class, 'submit'])
        ->name('submit')
        ->middleware('throttle:10,60');
});

// -----------------------------------------------------------------------
// Client Sales Offers — token-based, no auth required
// -----------------------------------------------------------------------
Route::prefix('offers')->name('offers.')->group(function () {
    Route::get('/{token}',         [\App\Http\Controllers\ClientSalesOfferController::class, 'show'])->name('show');
    Route::post('/{token}/accept', [\App\Http\Controllers\ClientSalesOfferController::class, 'accept'])->name('accept');
});
