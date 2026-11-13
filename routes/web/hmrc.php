<?php

use App\Http\Controllers\Finance\HmrcOAuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'has.business', 'can:view_finance', 'throttle:10,1'])
    ->prefix('admin/hmrc/oauth')
    ->name('admin.hmrc.oauth.')
    ->group(function (): void {
        Route::get('/connect', [HmrcOAuthController::class, 'connect'])->name('connect');
        Route::get('/callback', [HmrcOAuthController::class, 'callback'])->name('callback');
        Route::post('/refresh', [HmrcOAuthController::class, 'refresh'])->name('refresh');
        Route::delete('/disconnect', [HmrcOAuthController::class, 'disconnect'])->name('disconnect');
    });
