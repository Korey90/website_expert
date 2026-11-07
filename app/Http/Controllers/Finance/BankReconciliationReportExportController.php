<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\BankReconciliationReportRequest;
use App\Services\Finance\BankReconciliationReportService;
use App\Support\PermissionHelper;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BankReconciliationReportExportController extends Controller
{
    public function __invoke(BankReconciliationReportRequest $request, BankReconciliationReportService $service): StreamedResponse
    {
        abort_unless(PermissionHelper::allows($request->user(), 'view_finance'), 403);
        $business = currentBusiness();
        abort_unless($business, 403);
        $validated = $request->validated();
        $report = $service->report(
            $business->id,
            $validated['date_from'],
            $validated['date_to'],
            isset($validated['account_id']) ? (int) $validated['account_id'] : null,
        );

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [__('finance.bank_reconciliation_report.csv.account_summary')]);
            fputcsv($stream, array_map(
                fn (string $column): string => __('finance.bank_reconciliation_report.columns.'.$column),
                ['account', 'currency', 'opening_balance', 'inflows', 'outflows', 'closing_balance', 'allocated_amount', 'remaining_amount', 'reconciliation_rate', 'transactions'],
            ));

            foreach ($report['accounts'] as $account) {
                fputcsv($stream, [
                    $account->name,
                    $account->currency,
                    $account->opening_balance,
                    $account->inflows,
                    $account->outflows,
                    $account->closing_balance,
                    $account->allocated_amount,
                    $account->remaining_amount,
                    $account->reconciliation_rate.'%',
                    $account->transaction_count,
                ]);
            }

            fputcsv($stream, []);
            fputcsv($stream, [__('finance.bank_reconciliation_report.csv.transaction_details')]);
            fputcsv($stream, array_map(
                fn (string $column): string => __('finance.bank_reconciliation_report.columns.'.$column),
                ['date', 'account', 'reference', 'counterparty', 'amount', 'allocated_amount', 'remaining_amount', 'currency', 'status'],
            ));

            foreach ($report['transactions'] as $transaction) {
                fputcsv($stream, [
                    $transaction->booking_date,
                    $transaction->account_name,
                    $transaction->reference,
                    $transaction->counterparty,
                    $transaction->amount,
                    $transaction->allocated_amount,
                    $transaction->remaining_amount,
                    $transaction->currency,
                    __('finance.bank_reconciliation.statuses.'.$transaction->status),
                ]);
            }

            fclose($stream);
        }, 'bank-reconciliation-report-'.today()->toDateString().'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
