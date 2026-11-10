<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\VatDetailReportRequest;
use App\Services\Finance\VatDetailService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VatDetailReportExportController extends Controller
{
    public function __invoke(VatDetailReportRequest $request, VatDetailService $service): StreamedResponse
    {
        $business = currentBusiness();
        abort_unless($business, 403);
        $validated = $request->validated();
        $report = $service->report($business->id, $validated['date_from'], $validated['date_to']);

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [__('finance.vat_detail.csv.report')]);
            fputcsv($stream, [__('finance.vat_detail.csv.period'), $report['date_from'], $report['date_to']]);
            fputcsv($stream, []);
            fputcsv($stream, array_map(
                fn (string $column): string => __('finance.vat_detail.columns.'.$column),
                ['tax_point', 'document_type', 'reference', 'counterparty', 'currency', 'tax_code', 'rate', 'boxes', 'net_sales', 'net_purchases', 'output_vat', 'input_vat'],
            ));

            foreach ($report['rows'] as $row) {
                fputcsv($stream, [
                    $row->tax_point,
                    __('finance.vat_detail.document_types.'.$row->kind),
                    $row->reference,
                    $row->counterparty,
                    $row->currency,
                    $row->tax_code,
                    $row->tax_rate,
                    implode(', ', $row->mapping_boxes),
                    $row->net_sales_gbp,
                    $row->net_purchases_gbp,
                    $row->output_vat_gbp,
                    $row->input_vat_gbp,
                ]);
            }

            fputcsv($stream, []);
            foreach (['net_sales', 'net_purchases', 'output_vat', 'input_vat', 'net_vat', 'output_control', 'input_control', 'output_difference', 'input_difference'] as $metric) {
                fputcsv($stream, [
                    __('finance.vat_detail.metrics.'.$metric),
                    $report['totals']->{$metric.'_gbp'},
                ]);
            }

            fclose($stream);
        }, 'vat-detail-'.$report['date_from'].'-'.$report['date_to'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
