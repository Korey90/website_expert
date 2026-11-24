<?php

use App\Http\Controllers\AI\PublicAiChatController;
use Illuminate\Support\Facades\Route;

// Same-origin web middleware: session + CSRF. Never exempt POST endpoints from CSRF.
// Public tenant is configured server-side; no client-provided business_id is accepted.
Route::prefix('ai')->name('ai.')->group(function () {
    Route::get('/bootstrap', [PublicAiChatController::class, 'bootstrap'])
        ->name('bootstrap')->middleware('throttle:30,1');
    Route::get('/history', [PublicAiChatController::class, 'history'])
        ->name('history')->middleware('throttle:30,1');
    Route::post('/message', [PublicAiChatController::class, 'send'])
        ->name('message')->middleware('throttle:6,1');
    Route::post('/restart', [PublicAiChatController::class, 'restart'])
        ->name('restart')->middleware('throttle:5,1');
});
