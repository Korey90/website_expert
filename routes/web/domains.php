<?php

use App\Http\Controllers\Domain\DomainOrderController;
use App\Http\Controllers\Domain\PublicDomainController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Domains — public availability check
// -----------------------------------------------------------------------

Route::get('/domains',              [PublicDomainController::class, 'index'])->name('domains.index');
Route::get('/domains/check',        [PublicDomainController::class, 'check'])->name('domains.check');
Route::get('/domains/availability', [PublicDomainController::class, 'availability'])->name('domains.availability');

// -----------------------------------------------------------------------
// Domain order flow — requires auth (public-facing layout, not portal)
// -----------------------------------------------------------------------

Route::middleware('auth')->group(function () {
    Route::get('/domains/order',                        [DomainOrderController::class, 'order'])->name('domains.order');
    Route::post('/domains/order',                       [DomainOrderController::class, 'store'])->name('domains.order.store');
    Route::get('/domains/order/{order}/checkout',       [DomainOrderController::class, 'checkout'])->name('domains.checkout');
    Route::post('/domains/order/{order}/checkout',      [DomainOrderController::class, 'pay'])->name('domains.pay');
    Route::get('/domains/order/{order}/result',         [DomainOrderController::class, 'result'])->name('domains.result');
});
