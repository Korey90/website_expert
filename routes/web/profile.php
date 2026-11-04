<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\BriefingTemplatePdfController;
use App\Http\Controllers\CreditNotePdfController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialAccountController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Profile & account — auth required
// -----------------------------------------------------------------------

Route::middleware('auth')->group(function () {
    Route::get('portal/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::delete('/profile/social/{provider}', [SocialAccountController::class, 'destroy'])
        ->name('profile.social.unlink');

    Route::get('/profile/social/{provider}/connect', [SocialAuthController::class, 'connect'])
        ->name('profile.social.connect')
        ->where('provider', 'google|facebook');

    Route::get('/invoices/{invoice}/pdf', InvoicePdfController::class)->name('invoice.pdf');
    Route::get('/credit-notes/{creditNote}/pdf', CreditNotePdfController::class)->name('credit-note.pdf');

    Route::get('/briefing-templates/{briefingTemplate}/pdf', BriefingTemplatePdfController::class)
        ->name('briefing-template.pdf');
});
