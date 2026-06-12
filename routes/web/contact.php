<?php

use App\Http\Controllers\CalculatorLeadController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Leads\LeadCaptureController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Contact, About & Lead Capture — no auth required
// -----------------------------------------------------------------------

Route::get('/contact',         [ContactController::class, 'index'])->name('contact.index');
Route::get('/about-us',        [ContactController::class, 'aboutUs'])->name('about.index');
Route::post('/contact',        [ContactController::class, 'store'])->name('contact.store');
Route::post('/contact/quick',  [ContactController::class, 'quickStore'])->name('contact.quick');

Route::post('/calculator-lead', [CalculatorLeadController::class, 'store'])->name('calculator.lead');

Route::post('/leads', [LeadCaptureController::class, 'store'])
    ->name('leads.capture')
    ->middleware('throttle:3,60');
