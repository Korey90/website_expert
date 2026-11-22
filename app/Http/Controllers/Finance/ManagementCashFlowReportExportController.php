<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ManagementCashFlowReportRequest;
use App\Services\Finance\FinanceReportDocumentFactory;
use App\Services\Finance\FinanceReportExportService;
use App\Services\Finance\ManagementCashFlowService;
use Symfony\Component\HttpFoundation\Response;

class ManagementCashFlowReportExportController extends Controller
{
    public function __invoke(
        ManagementCashFlowReportRequest $request,
        ManagementCashFlowService $service,
        FinanceReportDocumentFactory $documents,
        FinanceReportExportService $exporter,
        string $format = 'csv',
    ): Response {
        $business = currentBusiness();
        abort_unless($business, 403);
        $validated = $request->validated();
        $report = $service->report(
            $business->id,
            (int) $validated['period_id'],
            isset($validated['comparison_period_id']) ? (int) $validated['comparison_period_id'] : null,
        );

        if ($format !== 'csv') {
            return $exporter->download($documents->managementCashFlow($business, $report), $format);
        }

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [__('finance.management_cash_flow.csv.report'), $report['period']->name]);
            fputcsv($stream, [__('finance.management_cash_flow.csv.period'), $report['period']->start_date, $report['period']->end_date]);
            fputcsv($stream, [
                __('finance.management_cash_flow.csv.comparison'),
                $report['comparison_period']?->name ?? __('finance.management_cash_flow.no_comparison'),
                $report['comparison_period']?->start_date,
                $report['comparison_period']?->end_date,
            ]);
            fputcsv($stream, []);
            fputcsv($stream, array_map(
                fn (string $column): string => __('finance.management_cash_flow.columns.'.$column),
                ['section', 'code', 'account', 'current', 'comparison', 'variance'],
            ));

            foreach ($report['sections'] as $section) {
                $label = __('finance.management_cash_flow.sections.'.$section->key);
                foreach ($section->rows as $row) {
                    fputcsv($stream, [
                        $label,
                        $row->code,
                        $row->name,
                        $row->amount_gbp,
                        $row->comparison_gbp,
                        $row->variance_gbp,
                    ]);
                }
                fputcsv($stream, [
                    __('finance.management_cash_flow.subtotal', ['section' => $label]),
                    '',
                    '',
                    $section->amount_gbp,
                    $section->comparison_gbp,
                    $section->variance_gbp,
                ]);
            }

            fputcsv($stream, []);
            foreach (['opening_cash', 'cash_inflows', 'cash_outflows', 'net_cash_flow', 'closing_cash', 'difference'] as $metric) {
                fputcsv($stream, [
                    __('finance.management_cash_flow.metrics.'.$metric),
                    '',
                    '',
                    $report['totals']->{$metric.'_gbp'},
                    $report['totals']->{'comparison_'.$metric.'_gbp'},
                    $report['totals']->{'variance_'.$metric.'_gbp'},
                ]);
            }

            fclose($stream);
        }, 'management-cash-flow-'.$report['period']->end_date.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
