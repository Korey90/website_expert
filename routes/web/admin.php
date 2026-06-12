<?php

use App\Http\Controllers\EmailTemplatePreviewController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Admin utilities — auth required
// -----------------------------------------------------------------------

Route::middleware('auth')->group(function () {

    // Lead note unpin (quick inline action)
    Route::patch('/lead-notes/{note}/unpin', function (\App\Models\LeadNote $note) {
        $note->update(['is_pinned' => false]);
        return back();
    })->name('lead-notes.unpin');

    // Email template preview (admin only)
    Route::get('/admin/email-preview/{template}/{locale?}', [EmailTemplatePreviewController::class, 'show'])
        ->name('admin.email-preview');

    // Reports — admin only
    Route::prefix('reports')->name('reports.')->group(function () {
        foreach (['html', 'pdf', 'xlsx', 'csv'] as $format) {
            Route::get("leads/{$format}",    [ReportController::class, 'leads'])->defaults('format', $format)->name("leads.{$format}");
            Route::get("invoices/{$format}", [ReportController::class, 'invoices'])->defaults('format', $format)->name("invoices.{$format}");
            Route::get("projects/{$format}", [ReportController::class, 'projects'])->defaults('format', $format)->name("projects.{$format}");
        }
    });
});
