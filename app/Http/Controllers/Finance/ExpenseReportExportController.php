<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Finance\ExpenseReportService;
use App\Services\Finance\FinanceReportDocumentFactory;
use App\Services\Finance\FinanceReportExportService;
use App\Support\PermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ExpenseReportExportController extends Controller
{
    public function __invoke(
        Request $request,
        ExpenseReportService $service,
        FinanceReportDocumentFactory $documents,
        FinanceReportExportService $exporter,
        string $format = 'csv',
    ): Response {
        abort_unless(PermissionHelper::allows($request->user(), 'view_finance'), 403);
        $business = currentBusiness();
        abort_unless($business, 403);
        $validated = $request->validate([
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'group_by' => ['required', Rule::in(ExpenseReportService::GROUPS)],
        ]);
        $report = $service->report($business->id, $validated['date_from'], $validated['date_to'], $validated['group_by']);

        if ($format !== 'csv') {
            return $exporter->download($documents->expense($business, $report, $validated), $format);
        }

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                __('finance.expense_report.columns.group'),
                __('finance.expense_report.columns.documents'),
                __('finance.expense_report.columns.net_gbp'),
                __('finance.expense_report.columns.vat_gbp'),
                __('finance.expense_report.columns.gross_gbp'),
            ]);

            foreach ($report['rows'] as $row) {
                fputcsv($stream, [$row->label, $row->document_count, $row->net_gbp, $row->vat_gbp, $row->gross_gbp]);
            }

            fclose($stream);
        }, 'expense-report-'.today()->toDateString().'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
