<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ClientBriefingController;
use App\Http\Controllers\ClientSalesOfferController;
use App\Http\Controllers\KalkulatorController;
use App\Http\Controllers\Landing\EnLandingController;
use App\Http\Controllers\Landing\PlLandingController;
use App\Http\Controllers\LandingPage\PublicLandingPageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\Seo\GoogleRankCheckerController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WelcomeController;
use App\Models\Article;
use App\Models\Page;
use App\Models\PortfolioProject;
use App\Models\ServiceItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Public pages — no auth required
// -----------------------------------------------------------------------

Route::get('/', WelcomeController::class)->name('home')->middleware('cache.marketing');
Route::get('/calculate', KalkulatorController::class)->name('kalkulator')->middleware('cache.marketing');

Route::get('/portfolio', [PortfolioController::class, 'index'])->name('portfolio.index')->middleware('cache.marketing');
Route::get('/portfolio/{slug}', [PortfolioController::class, 'show'])->name('portfolio.show')
    ->where('slug', '[a-z0-9\-]+')->middleware('cache.marketing');

Route::get('/services', [ServiceController::class, 'index'])->name('services.index')->middleware('cache.marketing');
Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('services.show')
    ->where('slug', '[a-z0-9\-]+')->middleware('cache.marketing');

Route::get('/blog', [ArticleController::class, 'index'])->name('blog.index')->middleware('cache.marketing');
Route::get('/blog/{slug}', [ArticleController::class, 'show'])->name('blog.show')
    ->where('slug', '[a-z0-9\-]+')->middleware('cache.marketing');

// -----------------------------------------------------------------------
// Free tools — no auth required
// -----------------------------------------------------------------------
Route::prefix('tools')->name('tools.')->group(function () {
    Route::get('/google-rank-checker', [GoogleRankCheckerController::class, 'show'])->name('google-rank-checker')->middleware('cache.marketing');
    Route::post('/google-rank-checker/check', [GoogleRankCheckerController::class, 'check'])
        ->name('google-rank-checker.check')
        ->middleware('throttle:10,60');
});

// Sitemap XML
Route::get('/sitemap.xml', function () {
    $content = Cache::remember('sitemap.xml', 3600, function () {
        $pages = Page::select('slug', 'updated_at')->get();
        $portfolio = PortfolioProject::select('slug', 'updated_at')
            ->where('is_active', true)->where('is_featured', true)->orderBy('sort_order')->get();
        $services = ServiceItem::select('slug', 'updated_at')
            ->where('is_active', true)->orderBy('sort_order')->get();
        $articles = Article::select('slug', 'updated_at')
            ->where('status', 'published')->orderByDesc('published_at')->get();
        $staticLastmod = now()->toAtomString();

        return view('sitemap', [
            'staticUrls' => [
                ['loc' => url('/'),                                    'priority' => '1.0', 'changefreq' => 'weekly',  'lastmod' => $staticLastmod],
                ['loc' => url('/en/web-design-northern-ireland'),      'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $staticLastmod],
                ['loc' => url('/pl/strony-internetowe-uk'),            'priority' => '0.9', 'changefreq' => 'monthly', 'lastmod' => $staticLastmod],
                ['loc' => url('/calculate'),                           'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $staticLastmod],
                ['loc' => url('/tools/google-rank-checker'),           'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $staticLastmod],
                ['loc' => url('/portfolio'),                           'priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => $staticLastmod],
                ['loc' => url('/services'),                            'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => $staticLastmod],
                ['loc' => url('/blog'),                                'priority' => '0.7', 'changefreq' => 'weekly',  'lastmod' => $staticLastmod],
            ],
            'pages' => $pages,
            'portfolio' => $portfolio,
            'services' => $services,
            'articles' => $articles,
        ])->render();
    });

    return response($content, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::get('/p/{slug}', [PageController::class, 'show'])->name('page.show')->middleware('cache.marketing');

// Explicit routes for legal/CMS pages — avoids conflict with the /{slug} catch-all in web.php
Route::get('/privacy-policy', [PageController::class, 'show'])->defaults('slug', 'privacy-policy')->name('page.privacy_policy')->middleware('cache.marketing');
Route::get('/terms-and-conditions', [PageController::class, 'show'])->defaults('slug', 'terms-and-conditions')->name('page.terms_and_conditions')->middleware('cache.marketing');
Route::get('/cookies', [PageController::class, 'show'])->defaults('slug', 'cookies')->name('page.cookies')->middleware('cache.marketing');
Route::get('/accessibility', [PageController::class, 'show'])->defaults('slug', 'accessibility')->name('page.accessibility')->middleware('cache.marketing');

Route::get('/lang/{locale}', function (string $locale) {
    $supported = array_keys(config('languages'));
    if (in_array($locale, $supported)) {
        session(['locale' => $locale]);
    }

    return redirect()->back(302, [], route('home'));
})->where('locale', '[a-z]{2}')->name('lang.switch');

// -----------------------------------------------------------------------
// Static Landing Pages (hardcoded, locale-prefixed, not CMS-managed)
// -----------------------------------------------------------------------
Route::prefix('pl')->name('pl.')->group(function () {
    Route::get('/strony-internetowe-uk',
        [PlLandingController::class, 'show'])
        ->name('strony-internetowe-uk')
        ->middleware('cache.marketing');

    Route::post('/strony-internetowe-uk/wyslij',
        [PlLandingController::class, 'submit'])
        ->name('strony-internetowe-uk.submit')
        ->middleware('throttle:5,60');
});

Route::prefix('en')->name('en.')->group(function () {
    Route::get('/web-design-northern-ireland',
        [EnLandingController::class, 'show'])
        ->name('web-design-northern-ireland')
        ->middleware('cache.marketing');

    Route::post('/web-design-northern-ireland/submit',
        [EnLandingController::class, 'submit'])
        ->name('web-design-northern-ireland.submit')
        ->middleware('throttle:5,60');
});

// -----------------------------------------------------------------------
// Public Landing Pages
// -----------------------------------------------------------------------
Route::prefix('lp')->name('lp.')->group(function () {
    Route::get('/{slug}', [PublicLandingPageController::class, 'show'])->name('show');
    Route::post('/{slug}/submit', [PublicLandingPageController::class, 'submit'])
        ->name('submit')
        ->middleware('throttle:3,60');
});

// -----------------------------------------------------------------------
// Client Briefings — token-based, no auth required
// -----------------------------------------------------------------------
Route::prefix('client/briefings')->name('client.briefings.')->group(function () {
    Route::get('/{token}', [ClientBriefingController::class, 'show'])->name('show');
    Route::patch('/{token}/save', [ClientBriefingController::class, 'autosave'])->name('autosave');
    Route::post('/{token}/submit', [ClientBriefingController::class, 'submit'])
        ->name('submit')
        ->middleware('throttle:10,60');
});

// -----------------------------------------------------------------------
// Client Sales Offers — token-based, no auth required
// -----------------------------------------------------------------------
Route::prefix('offers')->name('offers.')->group(function () {
    Route::get('/{token}', [ClientSalesOfferController::class, 'show'])->name('show');
    Route::post('/{token}/accept', [ClientSalesOfferController::class, 'accept'])->name('accept');
});
