<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\TrialBalanceReportRequest;
use App\Services\Finance\TrialBalanceService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrialBalanceReportExportController extends Controller
{
    public function __invoke(TrialBalanceReportRequest $request, TrialBalanceService $service): StreamedResponse
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
            fputcsv($stream, [__('finance.trial_balance.csv.report'), $report['period']->name]);
            fputcsv($stream, [__('finance.trial_balance.csv.period'), $report['period']->start_date, $report['period']->end_date]);
            fputcsv($stream, [
                __('finance.trial_balance.csv.comparison'),
                $report['comparison_period']?->name ?? __('finance.trial_balance.no_comparison'),
                $report['comparison_period']?->end_date,
            ]);
            fputcsv($stream, []);
            fputcsv($stream, array_map(
                fn (string $column): string => __('finance.trial_balance.columns.'.$column),
                [
                    'code', 'account', 'type', 'opening_debit', 'opening_credit', 'period_debit', 'period_credit',
                    'closing_debit', 'closing_credit', 'comparison_debit', 'comparison_credit', 'variance_debit', 'variance_credit',
                ],
            ));

            foreach ($report['rows'] as $row) {
                fputcsv($stream, [
                    $row->code,
                    $row->name,
                    __('finance.ledger_accounts.types.'.$row->type),
                    $row->opening_debit_gbp,
                    $row->opening_credit_gbp,
                    $row->period_debit_gbp,
                    $row->period_credit_gbp,
                    $row->closing_debit_gbp,
                    $row->closing_credit_gbp,
                    $row->comparison_debit_gbp,
                    $row->comparison_credit_gbp,
                    $row->variance_debit_gbp,
                    $row->variance_credit_gbp,
                ]);
            }

            $totals = $report['totals'];
            fputcsv($stream, [
                '',
                __('finance.trial_balance.total'),
                '',
                $totals->opening_debit_gbp,
                $totals->opening_credit_gbp,
                $totals->period_debit_gbp,
                $totals->period_credit_gbp,
                $totals->closing_debit_gbp,
                $totals->closing_credit_gbp,
                $totals->comparison_debit_gbp,
                $totals->comparison_credit_gbp,
                $totals->variance_debit_gbp,
                $totals->variance_credit_gbp,
            ]);

            fclose($stream);
        }, 'trial-balance-'.$report['period']->end_date.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
