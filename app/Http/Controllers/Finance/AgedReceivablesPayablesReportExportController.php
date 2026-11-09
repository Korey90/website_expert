<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\AgedReceivablesPayablesReportRequest;
use App\Services\Finance\AgedReceivablesPayablesService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgedReceivablesPayablesReportExportController extends Controller
{
    public function __invoke(
        AgedReceivablesPayablesReportRequest $request,
        AgedReceivablesPayablesService $service,
    ): StreamedResponse {
        $business = currentBusiness();
        abort_unless($business, 403);
        $report = $service->report($business->id, $request->validated('as_of'));

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [__('finance.aged_receivables_payables.csv.report')]);
            fputcsv($stream, [__('finance.aged_receivables_payables.csv.as_of'), $report['as_of']]);

            foreach ($report['sections'] as $section) {
                fputcsv($stream, []);
                fputcsv($stream, [__('finance.aged_receivables_payables.sections.'.$section->key)]);
                fputcsv($stream, array_map(
                    fn (string $column): string => __('finance.aged_receivables_payables.columns.'.$column),
                    ['counterparty', 'reference', 'issue_date', 'due_date', 'currency', 'days_overdue', 'bucket', 'balance'],
                ));

                foreach ($section->rows as $row) {
                    fputcsv($stream, [
                        $row->counterparty,
                        $row->reference,
                        $row->issue_date,
                        $row->due_date,
                        $row->currency,
                        $row->days_overdue,
                        __('finance.aged_receivables_payables.buckets.'.$row->bucket),
                        $row->balance_gbp,
                    ]);
                }

                fputcsv($stream, []);
                foreach ($report['buckets'] as $bucket) {
                    fputcsv($stream, [
                        __('finance.aged_receivables_payables.buckets.'.$bucket),
                        '', '', '', '', '', '',
                        $section->totals->{$bucket.'_gbp'},
                    ]);
                }
                fputcsv($stream, [
                    __('finance.aged_receivables_payables.metrics.total'),
                    '', '', '', '', '', '',
                    $section->totals->total_gbp,
                ]);
                fputcsv($stream, [
                    __('finance.aged_receivables_payables.metrics.control_balance'),
                    '', '', '', '', '', '',
                    $section->totals->control_balance_gbp,
                ]);
                fputcsv($stream, [
                    __('finance.aged_receivables_payables.metrics.difference'),
                    '', '', '', '', '', '',
                    $section->totals->difference_gbp,
                ]);
            }

            fclose($stream);
        }, 'aged-receivables-payables-'.$report['as_of'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
