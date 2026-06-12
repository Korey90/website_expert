<?php

use App\Http\Controllers\Onboarding\OnboardingController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Onboarding — no has.business middleware (would cause redirect loop)
// -----------------------------------------------------------------------

Route::middleware(['auth', 'verified'])
    ->prefix('onboarding')
    ->name('onboarding.')
    ->group(function () {
        Route::get('/',          [OnboardingController::class, 'index'])->name('index');
        Route::get('/profile',   [OnboardingController::class, 'profile'])->name('profile');
        Route::post('/profile',  [OnboardingController::class, 'saveProfile'])->name('profile.save');
        Route::get('/complete',  [OnboardingController::class, 'complete'])->name('complete');
    });
