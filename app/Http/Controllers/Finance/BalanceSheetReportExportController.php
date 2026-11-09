<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\BalanceSheetReportRequest;
use App\Services\Finance\BalanceSheetService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BalanceSheetReportExportController extends Controller
{
    public function __invoke(BalanceSheetReportRequest $request, BalanceSheetService $service): StreamedResponse
    {
        $business = currentBusiness();
        abort_unless($business, 403);
        $validated = $request->validated();
        $report = $service->report(
            $business->id,
            (int) $validated['period_id'],
            isset($validated['comparison_period_id']) ? (int) $validated['comparison_period_id'] : null,
        );

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [__('finance.balance_sheet.csv.report'), $report['period']->name]);
            fputcsv($stream, [__('finance.balance_sheet.csv.as_at'), $report['period']->end_date]);
            fputcsv($stream, [
                __('finance.balance_sheet.csv.comparison'),
                $report['comparison_period']?->name ?? __('finance.balance_sheet.no_comparison'),
                $report['comparison_period']?->end_date,
            ]);
            fputcsv($stream, []);
            fputcsv($stream, array_map(
                fn (string $column): string => __('finance.balance_sheet.columns.'.$column),
                ['section', 'code', 'account', 'current', 'comparison', 'variance'],
            ));

            foreach ($report['sections'] as $section) {
                $label = __('finance.balance_sheet.sections.'.$section->key);
                foreach ($section->rows as $row) {
                    fputcsv($stream, [
                        $label,
                        $row->code,
                        $row->is_synthetic
                            ? __('finance.balance_sheet.synthetic.'.$row->synthetic_key)
                            : $row->name,
                        $row->amount_gbp,
                        $row->comparison_gbp,
                        $row->variance_gbp,
                    ]);
                }
                fputcsv($stream, [
                    __('finance.balance_sheet.subtotal', ['section' => $label]),
                    '',
                    '',
                    $section->amount_gbp,
                    $section->comparison_gbp,
                    $section->variance_gbp,
                ]);
            }

            fputcsv($stream, []);
            foreach (['total_assets', 'total_liabilities', 'accumulated_earnings', 'total_equity', 'liabilities_and_equity', 'difference'] as $metric) {
                fputcsv($stream, [
                    __('finance.balance_sheet.metrics.'.$metric),
                    '',
                    '',
                    $report['totals']->{$metric.'_gbp'},
                    $report['totals']->{'comparison_'.$metric.'_gbp'},
                    $report['totals']->{'variance_'.$metric.'_gbp'},
                ]);
            }

            fclose($stream);
        }, 'balance-sheet-'.$report['period']->end_date.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
