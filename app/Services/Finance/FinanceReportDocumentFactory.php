<?php

namespace App\Services\Finance;

use App\Models\AccountingProfile;
use App\Models\Business;

class FinanceReportDocumentFactory
{
    /** @param array<string, mixed> $report */
    public function trialBalance(Business $business, array $report): array
    {
        $columns = [
            ['label' => __('finance.trial_balance.columns.code')],
            ['label' => __('finance.trial_balance.columns.account')],
            ['label' => __('finance.trial_balance.columns.type')],
            ...array_map(fn (string $key): array => ['label' => __('finance.trial_balance.columns.'.$key), 'type' => 'money'], [
                'opening_debit', 'opening_credit', 'period_debit', 'period_credit', 'closing_debit', 'closing_credit',
                'comparison_debit', 'comparison_credit', 'variance_debit', 'variance_credit',
            ]),
        ];
        $rows = $report['rows']->map(fn (object $row): array => ['values' => [
            $row->code, $row->name, __('finance.ledger_accounts.types.'.$row->type),
            $row->opening_debit_gbp, $row->opening_credit_gbp, $row->period_debit_gbp, $row->period_credit_gbp,
            $row->closing_debit_gbp, $row->closing_credit_gbp, $row->comparison_debit_gbp,
            $row->comparison_credit_gbp, $row->variance_debit_gbp, $row->variance_credit_gbp,
        ]])->all();
        $totals = $report['totals'];
        $rows[] = ['style' => 'total', 'values' => [
            '', __('finance.trial_balance.total'), '',
            $totals->opening_debit_gbp, $totals->opening_credit_gbp, $totals->period_debit_gbp, $totals->period_credit_gbp,
            $totals->closing_debit_gbp, $totals->closing_credit_gbp, $totals->comparison_debit_gbp,
            $totals->comparison_credit_gbp, $totals->variance_debit_gbp, $totals->variance_credit_gbp,
        ]];

        return $this->document($business, __('finance.trial_balance.title'), 'trial-balance-'.$report['period']->end_date, [
            __('finance.trial_balance.csv.period') => $report['period']->start_date.' - '.$report['period']->end_date,
            __('finance.trial_balance.csv.comparison') => $report['comparison_period']?->name ?? __('finance.trial_balance.no_comparison'),
        ], [['columns' => $columns, 'rows' => $rows]], 'a3');
    }

    /** @param array<string, mixed> $report @param array<string, mixed> $filters */
    public function expense(Business $business, array $report, array $filters): array
    {
        $rows = $report['rows']->map(fn (object $row): array => ['values' => [
            $row->label, $row->document_count, $row->net_gbp, $row->vat_gbp, $row->gross_gbp,
        ]])->all();
        $rows[] = ['style' => 'total', 'values' => [
            __('finance.exports.total'), $report['totals']->document_count, $report['totals']->net_gbp,
            $report['totals']->vat_gbp, $report['totals']->gross_gbp,
        ]];

        return $this->document($business, __('finance.expense_report.title'), 'expense-report-'.$filters['date_from'].'-'.$filters['date_to'], [
            __('finance.exports.period') => $filters['date_from'].' - '.$filters['date_to'],
            __('finance.expense_report.fields.group_by') => __('finance.expense_report.groups.'.$filters['group_by']),
        ], [[
            'columns' => [
                ['label' => __('finance.expense_report.columns.group')],
                ['label' => __('finance.expense_report.columns.documents'), 'type' => 'integer'],
                ['label' => __('finance.expense_report.columns.net_gbp'), 'type' => 'money'],
                ['label' => __('finance.expense_report.columns.vat_gbp'), 'type' => 'money'],
                ['label' => __('finance.expense_report.columns.gross_gbp'), 'type' => 'money'],
            ],
            'rows' => $rows,
        ]]);
    }

    /** @param array<string, mixed> $report */
    public function profitAndLoss(Business $business, array $report): array
    {
        return $this->sectionReport(
            $business,
            $report,
            'profit_and_loss',
            'profit-and-loss-'.$report['period']->end_date,
            ['gross_profit', 'operating_profit', 'profit_before_tax', 'net_profit'],
        );
    }

    /** @param array<string, mixed> $report */
    public function balanceSheet(Business $business, array $report): array
    {
        $rows = [];
        foreach ($report['sections'] as $section) {
            $label = __('finance.balance_sheet.sections.'.$section->key);
            foreach ($section->rows as $row) {
                $rows[] = ['values' => [
                    $label,
                    $row->code,
                    $row->is_synthetic ? __('finance.balance_sheet.synthetic.'.$row->synthetic_key) : $row->name,
                    $row->amount_gbp,
                    $row->comparison_gbp,
                    $row->variance_gbp,
                ]];
            }
            $rows[] = ['style' => 'subtotal', 'values' => [
                __('finance.balance_sheet.subtotal', ['section' => $label]), '', '',
                $section->amount_gbp, $section->comparison_gbp, $section->variance_gbp,
            ]];
        }

        return $this->document($business, __('finance.balance_sheet.title'), 'balance-sheet-'.$report['period']->end_date, [
            __('finance.balance_sheet.csv.as_at') => $report['period']->end_date,
            __('finance.balance_sheet.csv.comparison') => $report['comparison_period']?->name ?? __('finance.balance_sheet.no_comparison'),
        ], [
            $this->sixColumnTable('balance_sheet', $rows),
            $this->metricTable('balance_sheet', $report['totals'], [
                'total_assets', 'total_liabilities', 'accumulated_earnings', 'total_equity', 'liabilities_and_equity', 'difference',
            ]),
        ]);
    }

    /** @param array<string, mixed> $report @param array<string, mixed> $filters */
    public function bankReconciliation(Business $business, array $report, array $filters): array
    {
        $accountRows = $report['accounts']->map(fn (object $account): array => ['values' => [
            $account->name, $account->currency, $account->opening_balance, $account->inflows, $account->outflows,
            $account->closing_balance, $account->allocated_amount, $account->remaining_amount,
            $account->reconciliation_rate, $account->transaction_count,
        ]])->all();
        $transactionRows = $report['transactions']->map(fn (object $transaction): array => ['values' => [
            $transaction->booking_date, $transaction->account_name, $transaction->reference, $transaction->counterparty,
            $transaction->amount, $transaction->allocated_amount, $transaction->remaining_amount, $transaction->currency,
            __('finance.bank_reconciliation.statuses.'.$transaction->status),
        ]])->all();

        return $this->document($business, __('finance.bank_reconciliation_report.title'), 'bank-reconciliation-report-'.$filters['date_from'].'-'.$filters['date_to'], [
            __('finance.exports.period') => $filters['date_from'].' - '.$filters['date_to'],
        ], [
            [
                'title' => __('finance.bank_reconciliation_report.csv.account_summary'),
                'columns' => $this->columns('bank_reconciliation_report', [
                    'account', 'currency', 'opening_balance:number', 'inflows:number', 'outflows:number', 'closing_balance:number',
                    'allocated_amount:number', 'remaining_amount:number', 'reconciliation_rate:percent', 'transactions:integer',
                ]),
                'rows' => $accountRows,
            ],
            [
                'title' => __('finance.bank_reconciliation_report.csv.transaction_details'),
                'columns' => $this->columns('bank_reconciliation_report', [
                    'date', 'account', 'reference', 'counterparty', 'amount:number', 'allocated_amount:number',
                    'remaining_amount:number', 'currency', 'status',
                ]),
                'rows' => $transactionRows,
            ],
        ], 'a3');
    }

    /** @param array<string, mixed> $report */
    public function managementCashFlow(Business $business, array $report): array
    {
        return $this->sectionReport(
            $business,
            $report,
            'management_cash_flow',
            'management-cash-flow-'.$report['period']->end_date,
            ['opening_cash', 'cash_inflows', 'cash_outflows', 'net_cash_flow', 'closing_cash', 'difference'],
        );
    }

    /** @param array<string, mixed> $report */
    public function agedReceivablesPayables(Business $business, array $report): array
    {
        $tables = [];
        foreach ($report['sections'] as $section) {
            $rows = $section->rows->map(fn (object $row): array => ['values' => [
                $row->counterparty, $row->reference, $row->issue_date, $row->due_date, $row->currency,
                $row->days_overdue, __('finance.aged_receivables_payables.buckets.'.$row->bucket), $row->balance_gbp,
            ]])->all();
            foreach ($report['buckets'] as $bucket) {
                $rows[] = ['style' => 'subtotal', 'values' => [
                    __('finance.aged_receivables_payables.buckets.'.$bucket), '', '', '', '', '', '', $section->totals->{$bucket.'_gbp'},
                ]];
            }
            foreach (['total', 'control_balance', 'difference'] as $metric) {
                $rows[] = ['style' => 'total', 'values' => [
                    __('finance.aged_receivables_payables.metrics.'.$metric), '', '', '', '', '', '', $section->totals->{$metric.'_gbp'},
                ]];
            }
            $tables[] = [
                'title' => __('finance.aged_receivables_payables.sections.'.$section->key),
                'columns' => $this->columns('aged_receivables_payables', [
                    'counterparty', 'reference', 'issue_date', 'due_date', 'currency', 'days_overdue:integer', 'bucket', 'balance:money',
                ]),
                'rows' => $rows,
            ];
        }

        return $this->document($business, __('finance.aged_receivables_payables.title'), 'aged-receivables-payables-'.$report['as_of'], [
            __('finance.aged_receivables_payables.csv.as_of') => $report['as_of'],
        ], $tables, 'a3');
    }

    /** @param array<string, mixed> $report */
    public function vatDetail(Business $business, array $report): array
    {
        $rows = $report['rows']->map(fn (object $row): array => ['values' => [
            $row->tax_point, __('finance.vat_detail.document_types.'.$row->kind), $row->reference, $row->counterparty,
            $row->currency, $row->tax_code, $row->tax_rate, implode(', ', $row->mapping_boxes),
            $row->net_sales_gbp, $row->net_purchases_gbp, $row->output_vat_gbp, $row->input_vat_gbp,
        ]])->all();

        return $this->document($business, __('finance.vat_detail.title'), 'vat-detail-'.$report['date_from'].'-'.$report['date_to'], [
            __('finance.vat_detail.csv.period') => $report['date_from'].' - '.$report['date_to'],
        ], [
            [
                'columns' => $this->columns('vat_detail', [
                    'tax_point', 'document_type', 'reference', 'counterparty', 'currency', 'tax_code', 'rate:percent', 'boxes',
                    'net_sales:money', 'net_purchases:money', 'output_vat:money', 'input_vat:money',
                ]),
                'rows' => $rows,
            ],
            $this->simpleMetricTable('vat_detail', $report['totals'], [
                'net_sales', 'net_purchases', 'output_vat', 'input_vat', 'net_vat', 'output_control', 'input_control',
                'output_difference', 'input_difference',
            ]),
        ], 'a3');
    }

    /** @param array<string, mixed> $report @param list<string> $metrics */
    private function sectionReport(Business $business, array $report, string $key, string $filename, array $metrics): array
    {
        $rows = [];
        foreach ($report['sections'] as $section) {
            $label = __('finance.'.$key.'.sections.'.$section->key);
            foreach ($section->rows as $row) {
                $rows[] = ['values' => [$label, $row->code, $row->name, $row->amount_gbp, $row->comparison_gbp, $row->variance_gbp]];
            }
            $rows[] = ['style' => 'subtotal', 'values' => [
                __('finance.'.$key.'.subtotal', ['section' => $label]), '', '',
                $section->amount_gbp, $section->comparison_gbp, $section->variance_gbp,
            ]];
        }

        return $this->document($business, __('finance.'.$key.'.title'), $filename, [
            __('finance.'.$key.'.csv.period') => $report['period']->start_date.' - '.$report['period']->end_date,
            __('finance.'.$key.'.csv.comparison') => $report['comparison_period']?->name ?? __('finance.'.$key.'.no_comparison'),
        ], [
            $this->sixColumnTable($key, $rows),
            $this->metricTable($key, $report['totals'], $metrics),
        ]);
    }

    /** @param list<array<string, mixed>> $rows */
    private function sixColumnTable(string $key, array $rows): array
    {
        return [
            'columns' => $this->columns($key, ['section', 'code', 'account', 'current:money', 'comparison:money', 'variance:money']),
            'rows' => $rows,
        ];
    }

    /** @param list<string> $metrics */
    private function metricTable(string $key, object $totals, array $metrics): array
    {
        return [
            'title' => __('finance.exports.summary'),
            'columns' => [
                ['label' => __('finance.exports.metric')],
                ['label' => __('finance.'.$key.'.columns.current'), 'type' => 'money'],
                ['label' => __('finance.'.$key.'.columns.comparison'), 'type' => 'money'],
                ['label' => __('finance.'.$key.'.columns.variance'), 'type' => 'money'],
            ],
            'rows' => array_map(fn (string $metric): array => ['style' => 'total', 'values' => [
                __('finance.'.$key.'.metrics.'.$metric),
                $totals->{$metric.'_gbp'},
                $totals->{'comparison_'.$metric.'_gbp'},
                $totals->{'variance_'.$metric.'_gbp'},
            ]], $metrics),
        ];
    }

    /** @param list<string> $metrics */
    private function simpleMetricTable(string $key, object $totals, array $metrics): array
    {
        return [
            'title' => __('finance.exports.summary'),
            'columns' => [
                ['label' => __('finance.exports.metric')],
                ['label' => __('finance.exports.amount_gbp'), 'type' => 'money'],
            ],
            'rows' => array_map(fn (string $metric): array => ['style' => 'total', 'values' => [
                __('finance.'.$key.'.metrics.'.$metric), $totals->{$metric.'_gbp'},
            ]], $metrics),
        ];
    }

    /** @param list<string> $definitions */
    private function columns(string $key, array $definitions): array
    {
        return array_map(function (string $definition) use ($key): array {
            [$name, $type] = array_pad(explode(':', $definition, 2), 2, 'text');

            return ['label' => __('finance.'.$key.'.columns.'.$name), 'type' => $type];
        }, $definitions);
    }

    /** @param array<string, string> $metadata @param list<array<string, mixed>> $tables */
    private function document(
        Business $business,
        string $title,
        string $filename,
        array $metadata,
        array $tables,
        string $paper = 'a4',
    ): array {
        $legalName = AccountingProfile::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->value('legal_name');

        return [
            'title' => $title,
            'filename' => $filename,
            'business_name' => filled($legalName) ? $legalName : $business->name,
            'generated_at' => now()->format('Y-m-d H:i:s T'),
            'metadata' => $metadata,
            'tables' => $tables,
            'paper' => $paper,
            'orientation' => 'landscape',
        ];
    }
}
