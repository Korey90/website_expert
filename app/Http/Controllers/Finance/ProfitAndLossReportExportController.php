<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ProfitAndLossReportRequest;
use App\Services\Finance\FinanceReportDocumentFactory;
use App\Services\Finance\FinanceReportExportService;
use App\Services\Finance\ProfitAndLossService;
use Symfony\Component\HttpFoundation\Response;

class ProfitAndLossReportExportController extends Controller
{
    public function __invoke(
        ProfitAndLossReportRequest $request,
        ProfitAndLossService $service,
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
            return $exporter->download($documents->profitAndLoss($business, $report), $format);
        }

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [__('finance.profit_and_loss.csv.report'), $report['period']->name]);
            fputcsv($stream, [__('finance.profit_and_loss.csv.period'), $report['period']->start_date, $report['period']->end_date]);
            fputcsv($stream, [
                __('finance.profit_and_loss.csv.comparison'),
                $report['comparison_period']?->name ?? __('finance.profit_and_loss.no_comparison'),
                $report['comparison_period']?->start_date,
                $report['comparison_period']?->end_date,
            ]);
            fputcsv($stream, []);
            fputcsv($stream, array_map(
                fn (string $column): string => __('finance.profit_and_loss.columns.'.$column),
                ['section', 'code', 'account', 'current', 'comparison', 'variance'],
            ));

            foreach ($report['sections'] as $section) {
                $label = __('finance.profit_and_loss.sections.'.$section->key);
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
                    __('finance.profit_and_loss.subtotal', ['section' => $label]),
                    '',
                    '',
                    $section->amount_gbp,
                    $section->comparison_gbp,
                    $section->variance_gbp,
                ]);
            }

            fputcsv($stream, []);
            foreach (['gross_profit', 'operating_profit', 'profit_before_tax', 'net_profit'] as $metric) {
                fputcsv($stream, [
                    __('finance.profit_and_loss.metrics.'.$metric),
                    '',
                    '',
                    $report['totals']->{$metric.'_gbp'},
                    $report['totals']->{'comparison_'.$metric.'_gbp'},
                    $report['totals']->{'variance_'.$metric.'_gbp'},
                ]);
            }

            fclose($stream);
        }, 'profit-and-loss-'.$report['period']->end_date.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
