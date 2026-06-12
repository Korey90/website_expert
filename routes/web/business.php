<?php

use App\Http\Controllers\Business\ApiTokenController;
use App\Http\Controllers\Business\BusinessController;
use App\Http\Controllers\Business\BusinessProfileController;
use App\Http\Controllers\LandingPage\AiLandingGeneratorController;
use App\Http\Controllers\LandingPage\LandingPageController;
use App\Http\Controllers\LandingPage\LandingPageSectionController;
use App\Http\Controllers\Leads\LeadWebController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Business settings, CRM, API tokens, Landing Pages — auth + has.business
// -----------------------------------------------------------------------

Route::middleware(['auth', 'verified', 'has.business'])->group(function () {

    // Business settings
    Route::get('/business/settings',    [BusinessController::class, 'edit'])->name('business.edit');
    Route::patch('/business/settings',  [BusinessController::class, 'update'])->name('business.update');
    Route::post('/business/logo',       [BusinessController::class, 'uploadLogo'])->name('business.logo.upload');
    Route::delete('/business/logo',     [BusinessController::class, 'deleteLogo'])->name('business.logo.delete');

    // Business profile
    Route::get('/business/profile',             [BusinessProfileController::class, 'edit'])->name('business.profile.edit');
    Route::patch('/business/profile',           [BusinessProfileController::class, 'update'])->name('business.profile.update');
    Route::get('/business/profile/completion',  [BusinessProfileController::class, 'completion'])->name('business.profile.completion');

    // Leads — Inertia views + lead actions
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('/{lead}',         [LeadWebController::class, 'show'])->name('show');
        Route::put('/{lead}/assign',  [LeadWebController::class, 'assign'])->name('assign');
        Route::put('/{lead}/stage',   [LeadWebController::class, 'stage'])->name('stage');
        Route::post('/{lead}/won',    [LeadWebController::class, 'won'])->name('won');
        Route::post('/{lead}/lost',   [LeadWebController::class, 'lost'])->name('lost');
    });

    // API Tokens (for external integrations — Zapier, Make.com)
    Route::prefix('business/api-tokens')->name('business.api-tokens.')->group(function () {
        Route::get('/',           [ApiTokenController::class, 'index'])->name('index');
        Route::post('/',          [ApiTokenController::class, 'store'])->name('store');
        Route::delete('/{token}', [ApiTokenController::class, 'destroy'])->name('destroy');
    });

    // Landing Pages — live under /portal
    Route::middleware('landing-page.tenant')
        ->prefix('portal/landing-pages')
        ->name('landing-pages.')
        ->group(function () {
            Route::get('/',                   [LandingPageController::class, 'index'])->name('index');
            Route::get('/create',             [LandingPageController::class, 'create'])->name('create');
            Route::get('/ai/create',          [LandingPageController::class, 'createWithAi'])->name('ai.create');
            Route::post('/',                  [LandingPageController::class, 'store'])->name('store');
            Route::get('/{landingPage}',      [LandingPageController::class, 'show'])->name('show');
            Route::get('/{landingPage}/edit', [LandingPageController::class, 'edit'])->name('edit');
            Route::patch('/{landingPage}',    [LandingPageController::class, 'update'])->name('update');
            Route::delete('/{landingPage}',   [LandingPageController::class, 'destroy'])->name('destroy');
            Route::post('/{landingPage}/publish',   [LandingPageController::class, 'publish'])->name('publish');
            Route::post('/{landingPage}/unpublish', [LandingPageController::class, 'unpublish'])->name('unpublish');

            Route::prefix('ai')->name('ai.')->group(function () {
                Route::post('/generate', [AiLandingGeneratorController::class, 'generate'])->name('generate');
                Route::post('/variants/{variant}/regenerate-section', [AiLandingGeneratorController::class, 'regenerateSection'])
                    ->name('regenerate-section');
                Route::post('/variants/{variant}/save', [AiLandingGeneratorController::class, 'save'])->name('save');
            });

            Route::prefix('/{landingPage}/sections')->name('sections.')->group(function () {
                Route::post('/',              [LandingPageSectionController::class, 'store'])->name('store');
                Route::patch('/{section}',    [LandingPageSectionController::class, 'update'])->name('update');
                Route::delete('/{section}',   [LandingPageSectionController::class, 'destroy'])->name('destroy');
                Route::post('/reorder',       [LandingPageSectionController::class, 'reorder'])->name('reorder');
            });
        });
});
