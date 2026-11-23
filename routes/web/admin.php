<?php

use App\Http\Controllers\Domain\DomainPriceListCsvController;
use App\Http\Controllers\EmailTemplatePreviewController;
use App\Http\Controllers\Finance\AccountantPackController;
use App\Http\Controllers\Finance\AgedReceivablesPayablesReportExportController;
use App\Http\Controllers\Finance\BalanceSheetReportExportController;
use App\Http\Controllers\Finance\BankReconciliationReportExportController;
use App\Http\Controllers\Finance\ExpenseReportExportController;
use App\Http\Controllers\Finance\FinancialAttachmentController;
use App\Http\Controllers\Finance\HmrcVatReceiptController;
use App\Http\Controllers\Finance\ManagementCashFlowReportExportController;
use App\Http\Controllers\Finance\ProfitAndLossReportExportController;
use App\Http\Controllers\Finance\TrialBalanceReportExportController;
use App\Http\Controllers\Finance\UniversalCreditEvidencePackController;
use App\Http\Controllers\Finance\VatDetailReportExportController;
use App\Http\Controllers\ReportController;
use App\Models\LeadNote;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------
// Admin utilities — auth required
// -----------------------------------------------------------------------

Route::middleware('auth')->group(function () {

    // Lead note unpin (quick inline action)
    Route::patch('/lead-notes/{note}/unpin', function (LeadNote $note) {
        abort_unless(
            $note->lead?->business_id === currentBusiness()?->id
                || auth()->user()?->can('view_businesses'),
            403,
        );
        $note->update(['is_pinned' => false]);

        return back();
    })->middleware('can:edit_leads')->name('lead-notes.unpin');

    // Email template preview (admin only)
    Route::get('/admin/email-preview/{template}/{locale?}', [EmailTemplatePreviewController::class, 'show'])
        ->middleware('can:view_email_templates')
        ->name('admin.email-preview');

    Route::middleware('can:view_finance')->prefix('admin/finance/attachments')->name('admin.finance.attachments.')->group(function () {
        Route::get('/{attachment}/preview', [FinancialAttachmentController::class, 'preview'])
            ->name('preview');
        Route::get('/{attachment}/download', [FinancialAttachmentController::class, 'download'])
            ->name('download');
    });

    $financeReportExports = [
        'expense-report' => ExpenseReportExportController::class,
        'bank-reconciliation-report' => BankReconciliationReportExportController::class,
        'trial-balance' => TrialBalanceReportExportController::class,
        'profit-and-loss' => ProfitAndLossReportExportController::class,
        'balance-sheet' => BalanceSheetReportExportController::class,
        'management-cash-flow' => ManagementCashFlowReportExportController::class,
        'aged-receivables-payables' => AgedReceivablesPayablesReportExportController::class,
        'vat-detail' => VatDetailReportExportController::class,
    ];

    foreach ($financeReportExports as $report => $controller) {
        foreach (['csv', 'xlsx', 'pdf'] as $format) {
            Route::get("/admin/finance/{$report}.{$format}", $controller)
                ->defaults('format', $format)
                ->middleware('can:view_finance')
                ->name("admin.finance.{$report}.{$format}");
        }
    }

    Route::get('/admin/finance/vat-returns/{vatReturn}/accountant-pack.zip', AccountantPackController::class)
        ->middleware('can:view_finance')
        ->name('admin.finance.vat-returns.accountant-pack');

    Route::get('/admin/finance/vat-returns/{vatReturn}/hmrc-receipt.pdf', HmrcVatReceiptController::class)
        ->middleware('can:submit_tax_returns')
        ->name('admin.finance.vat-returns.hmrc-receipt');

    Route::get('/admin/finance/universal-credit/evidence-packs/{evidencePack}.zip', UniversalCreditEvidencePackController::class)
        ->middleware('can:export_universal_credit_pack')
        ->name('admin.finance.universal-credit.evidence-packs.download');

    // Reports — admin only
    Route::middleware(['can:view_reports', 'can:export_reports'])->prefix('reports')->name('reports.')->group(function () {
        foreach (['html', 'pdf', 'xlsx', 'csv'] as $format) {
            Route::get("leads/{$format}", [ReportController::class, 'leads'])->defaults('format', $format)->name("leads.{$format}");
            Route::get("invoices/{$format}", [ReportController::class, 'invoices'])->defaults('format', $format)->name("invoices.{$format}");
            Route::get("projects/{$format}", [ReportController::class, 'projects'])->defaults('format', $format)->name("projects.{$format}");
        }
    });

    // Domain price list CSV export
    Route::get('/admin/domain-price-lists/export', [DomainPriceListCsvController::class, 'export'])
        ->middleware('can:manage_domain_pricing')
        ->name('admin.domain-price-list.export');

    Route::get('/admin/domain-price-lists/export-template', [DomainPriceListCsvController::class, 'exportTemplate'])
        ->middleware('can:manage_domain_pricing')
        ->name('admin.domain-price-list.export-template');
});
