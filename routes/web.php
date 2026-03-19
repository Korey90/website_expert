<?php

use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

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
