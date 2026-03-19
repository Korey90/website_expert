<?php

use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\KalkulatorController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');
Route::get('/kalkulator', KalkulatorController::class)->name('kalkulator');
Route::get('/p/{slug}', [PageController::class, 'show'])->name('page.show');

Route::get('/lang/{locale}', function (string $locale) {
    $supported = array_keys(config('languages'));
    if (in_array($locale, $supported)) {
        session(['locale' => $locale]);
    }
    return redirect()->back(302, [], route('home'));
})->where('locale', '[a-z]{2}')->name('lang.switch');

Route::post('/contact', function (\Illuminate\Http\Request $request) {
    // TODO: implement email sending / store in DB
    return back()->with('success', 'Wiadomosc wyslana.');
})->name('contact.store');

Route::get('/dashboard', function () {
    return inertia('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/invoices/{invoice}/pdf', InvoicePdfController::class)->name('invoice.pdf');
});

require __DIR__.'/auth.php';
