<?php

use App\Http\Controllers\Domain\DomainPriceListCsvController;
use App\Http\Controllers\EmailTemplatePreviewController;
use App\Http\Controllers\Finance\BalanceSheetReportExportController;
use App\Http\Controllers\Finance\BankReconciliationReportExportController;
use App\Http\Controllers\Finance\ExpenseReportExportController;
use App\Http\Controllers\Finance\FinancialAttachmentController;
use App\Http\Controllers\Finance\ProfitAndLossReportExportController;
use App\Http\Controllers\Finance\TrialBalanceReportExportController;
use App\Http\Controllers\ReportController;
use App\Models\LeadNote;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Admin utilities — auth required
// -----------------------------------------------------------------------

Route::middleware('auth')->group(function () {

    // Lead note unpin (quick inline action)
    Route::patch('/lead-notes/{note}/unpin', function (LeadNote $note) {
        $note->update(['is_pinned' => false]);

        return back();
    })->name('lead-notes.unpin');

    // Email template preview (admin only)
    Route::get('/admin/email-preview/{template}/{locale?}', [EmailTemplatePreviewController::class, 'show'])
        ->name('admin.email-preview');

    Route::prefix('admin/finance/attachments')->name('admin.finance.attachments.')->group(function () {
        Route::get('/{attachment}/preview', [FinancialAttachmentController::class, 'preview'])
            ->name('preview');
        Route::get('/{attachment}/download', [FinancialAttachmentController::class, 'download'])
            ->name('download');
    });

    Route::get('/admin/finance/expense-report.csv', ExpenseReportExportController::class)
        ->name('admin.finance.expense-report.csv');

    Route::get('/admin/finance/bank-reconciliation-report.csv', BankReconciliationReportExportController::class)
        ->name('admin.finance.bank-reconciliation-report.csv');

    Route::get('/admin/finance/trial-balance.csv', TrialBalanceReportExportController::class)
        ->name('admin.finance.trial-balance.csv');

    Route::get('/admin/finance/profit-and-loss.csv', ProfitAndLossReportExportController::class)
        ->name('admin.finance.profit-and-loss.csv');

    Route::get('/admin/finance/balance-sheet.csv', BalanceSheetReportExportController::class)
        ->name('admin.finance.balance-sheet.csv');

    // Reports — admin only
    Route::prefix('reports')->name('reports.')->group(function () {
        foreach (['html', 'pdf', 'xlsx', 'csv'] as $format) {
            Route::get("leads/{$format}", [ReportController::class, 'leads'])->defaults('format', $format)->name("leads.{$format}");
            Route::get("invoices/{$format}", [ReportController::class, 'invoices'])->defaults('format', $format)->name("invoices.{$format}");
            Route::get("projects/{$format}", [ReportController::class, 'projects'])->defaults('format', $format)->name("projects.{$format}");
        }
    });

    // Domain price list CSV export
    Route::get('/admin/domain-price-lists/export', [DomainPriceListCsvController::class, 'export'])
        ->name('admin.domain-price-list.export');

    Route::get('/admin/domain-price-lists/export-template', [DomainPriceListCsvController::class, 'exportTemplate'])
        ->name('admin.domain-price-list.export-template');
});
