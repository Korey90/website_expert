<?php

namespace App\Services\Finance;

use App\Models\AccountingProfile;
use App\Models\Business;

class UniversalCreditReportDocumentFactory
{
    /** @param array<string, mixed> $report */
    public function make(Business $business, array $report): array
    {
        $summary = $report['summary'];
        $detailRows = array_map(fn (array $row): array => ['values' => [
            $row['cash_date'],
            __('finance.universal_credit.values.direction.'.$row['direction']),
            __('finance.universal_credit.values.category.'.$row['category']),
            __('finance.universal_credit.values.classification.'.$row['classification_status']),
            $row['source_type'],
            $row['source_reference'],
            $row['original_amount'],
            $row['original_currency'],
            $this->gbp($row['gross_amount_pence']),
            $this->gbp($row['vat_amount_pence']),
            $row['business_use_basis_points'] / 100,
            $this->gbp($row['reportable_amount_pence']),
            $row['exclusion_reason'],
            __('finance.universal_credit.values.evidence.'.$row['evidence']['state']),
            __('finance.universal_credit.values.reconciliation.'.$row['reconciliation']['state']),
        ]], $report['details']);
        $simplifiedRows = array_map(fn (array $row): array => ['values' => [
            __('finance.universal_credit.values.simplified_type.'.$row['type']),
            __('finance.universal_credit.values.simplified_status.'.$row['status']),
            $row['description'],
            $this->gbp($row['calculated_amount_pence']),
            __('finance.universal_credit.values.evidence.'.$row['evidence']['state']),
        ]], $report['simplified_expenses']);
        $reconciliationRows = array_map(fn (array $row): array => ['values' => [
            $row['item_id'], $row['source_type'], $row['source_reference'],
            __('finance.universal_credit.values.reconciliation.'.$row['state']),
            $row['transaction_amount'] ?? null, $row['unreconciled_amount'] ?? null,
        ]], $report['reconciliation']);
        $legalName = AccountingProfile::withoutGlobalScopes()
            ->where('business_id', $business->id)->value('legal_name');

        return [
            'title' => __('finance.universal_credit.report.title'),
            'filename' => 'universal-credit-'.$report['period']['number'],
            'business_name' => filled($legalName) ? $legalName : $business->name,
            'generated_at' => now()->format('Y-m-d H:i:s T'),
            'metadata' => [
                __('finance.universal_credit.report.period_number') => $report['period']['number'],
                __('finance.exports.period') => $report['period']['date_from'].' - '.$report['period']['date_to'],
                __('finance.universal_credit.report.vat_method') => $report['period']['vat_reporting_method'] ?? __('finance.universal_credit.report.not_applicable'),
                __('finance.universal_credit.report.snapshot_hash') => $report['period']['source_snapshot_hash'],
            ],
            'tables' => [
                [
                    'title' => __('finance.exports.summary'),
                    'columns' => [
                        ['label' => __('finance.exports.metric')],
                        ['label' => __('finance.exports.amount_gbp'), 'type' => 'money'],
                    ],
                    'rows' => [
                        ['style' => 'total', 'values' => [__('finance.universal_credit.report.income_total'), $this->gbp($summary['income_total_pence'])]],
                        ['style' => 'total', 'values' => [__('finance.universal_credit.report.expense_total'), $this->gbp($summary['expense_total_pence'])]],
                        ['style' => 'total', 'values' => [__('finance.universal_credit.report.net_total'), $this->gbp($summary['net_total_pence'])]],
                    ],
                ],
                [
                    'title' => __('finance.universal_credit.report.details'),
                    'columns' => $this->columns([
                        'cash_date', 'direction', 'category', 'classification', 'source_type', 'source_reference',
                        'original_amount:number', 'currency', 'gross:money', 'vat:money', 'business_use:percent',
                        'reportable:money', 'exclusion_reason', 'evidence', 'reconciliation',
                    ]),
                    'rows' => $detailRows,
                ],
                [
                    'title' => __('finance.universal_credit.report.simplified_expenses'),
                    'columns' => $this->columns(['type', 'status', 'description', 'reportable:money', 'evidence']),
                    'rows' => $simplifiedRows,
                ],
                [
                    'title' => __('finance.universal_credit.report.reconciliation'),
                    'columns' => $this->columns(['item_id:integer', 'source_type', 'source_reference', 'status', 'transaction_amount:number', 'unreconciled_amount:number']),
                    'rows' => $reconciliationRows,
                ],
            ],
            'paper' => 'a3',
            'orientation' => 'landscape',
        ];
    }

    /** @param list<string> $definitions */
    private function columns(array $definitions): array
    {
        return array_map(function (string $definition): array {
            [$name, $type] = array_pad(explode(':', $definition, 2), 2, 'text');

            return ['label' => __('finance.universal_credit.report.columns.'.$name), 'type' => $type];
        }, $definitions);
    }

    private function gbp(?int $pence): ?string
    {
        return $pence === null ? null : number_format($pence / 100, 2, '.', '');
    }
}
