<?php

namespace App\Services\Finance;

use App\Models\BankTransaction;
use App\Models\ExpensePayment;
use App\Models\Payment;
use App\Models\PurchaseCreditRefund;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditReportItem;

class UniversalCreditReportModelService
{
    public function __construct(
        private readonly UniversalCreditEvidenceService $evidence,
    ) {}

    /** @return array<string, mixed> */
    public function build(UniversalCreditAssessmentPeriod $period): array
    {
        $items = UniversalCreditReportItem::withoutGlobalScopes()
            ->where('business_id', $period->business_id)
            ->where('assessment_period_id', $period->id)
            ->orderBy('cash_date')->orderBy('id')->get();
        $simplified = $period->simplifiedExpenses()->withoutGlobalScopes()
            ->where('business_id', $period->business_id)->orderBy('id')->get();

        $details = $items->map(function (UniversalCreditReportItem $item): array {
            $evidence = $this->evidence->forItem($item);

            return [
                'id' => $item->id,
                'cash_date' => $item->cash_date->toDateString(),
                'direction' => $item->direction,
                'category' => $item->category,
                'classification_status' => $item->classification_status,
                'source_type' => class_basename($item->source_type),
                'source_reference' => $this->sourceReference($item),
                'original_amount' => (string) $item->original_amount,
                'original_currency' => $item->original_currency,
                'gross_amount_pence' => $item->gross_amount_pence,
                'vat_amount_pence' => $item->vat_amount_pence,
                'business_use_basis_points' => $item->business_use_basis_points,
                'business_share_pence' => $item->business_share_pence,
                'reportable_amount_pence' => $item->reportable_amount_pence,
                'calculation_method' => $item->calculation_method,
                'calculation_rule' => $item->calculation_rule,
                'source_is_current' => $item->source_is_current,
                'exclusion_reason' => $item->classification_status === UniversalCreditReportItem::STATUS_EXCLUDED
                    ? $item->classification_reason : null,
                'evidence' => $evidence,
                'reconciliation' => $this->reconciliation($item),
            ];
        })->values()->all();

        $simplifiedRows = $simplified->map(fn ($expense): array => [
            'id' => $expense->id,
            'type' => $expense->type,
            'status' => $expense->status,
            'description' => $expense->description,
            'inputs' => $expense->inputs,
            'calculated_amount_pence' => $expense->calculated_amount_pence,
            'evidence' => $this->evidence->forSimplified($expense),
        ])->values()->all();

        return [
            'schema_version' => 1,
            'period' => [
                'id' => $period->id,
                'number' => $period->number,
                'date_from' => $period->date_from->toDateString(),
                'date_to' => $period->date_to->toDateString(),
                'vat_reporting_method' => $period->vat_reporting_method_snapshot,
                'policy_rules_hash' => $period->policy_rules_hash,
                'source_snapshot_hash' => $period->source_snapshot_hash,
            ],
            'summary' => [
                'income_total_pence' => $period->income_total_pence,
                'expense_total_pence' => $period->expense_total_pence,
                'net_total_pence' => $period->net_total_pence,
                'included_items' => collect($details)->where('classification_status', UniversalCreditReportItem::STATUS_INCLUDED)->count(),
                'excluded_items' => collect($details)->where('classification_status', UniversalCreditReportItem::STATUS_EXCLUDED)->count(),
                'simplified_expenses' => collect($simplifiedRows)->where('status', 'active')->count(),
            ],
            'details' => $details,
            'simplified_expenses' => $simplifiedRows,
            'reconciliation' => array_map(fn (array $row): array => [
                'item_id' => $row['id'],
                'source_type' => $row['source_type'],
                'source_reference' => $row['source_reference'],
                ...$row['reconciliation'],
            ], $details),
        ];
    }

    private function sourceReference(UniversalCreditReportItem $item): string
    {
        if ($item->source_type === (new UniversalCreditManualEntry)->getMorphClass()) {
            return (string) (UniversalCreditManualEntry::withoutGlobalScopes()
                ->where('business_id', $item->business_id)->find($item->source_id)?->description ?? '');
        }

        if ($item->source_type === (new BankTransaction)->getMorphClass()) {
            return (string) (BankTransaction::withoutGlobalScopes()
                ->where('business_id', $item->business_id)->find($item->source_id)?->reference ?? '');
        }

        if ($item->source_type === (new Payment)->getMorphClass()) {
            return (string) (Payment::withoutGlobalScopes()->with('invoice')
                ->where('business_id', $item->business_id)->find($item->source_id)?->invoice?->number ?? '');
        }

        if ($item->source_type === (new ExpensePayment)->getMorphClass()) {
            return (string) (ExpensePayment::withoutGlobalScopes()->with('expense')
                ->where('business_id', $item->business_id)->find($item->source_id)?->expense?->supplier_invoice_number ?? '');
        }

        if ($item->source_type === (new PurchaseCreditRefund)->getMorphClass()) {
            return (string) (PurchaseCreditRefund::withoutGlobalScopes()->with('purchaseCreditNote')
                ->where('business_id', $item->business_id)->find($item->source_id)?->purchaseCreditNote?->number ?? '');
        }

        return '';
    }

    /** @return array<string, mixed> */
    private function reconciliation(UniversalCreditReportItem $item): array
    {
        if ($item->source_type !== (new BankTransaction)->getMorphClass()) {
            return ['state' => $item->source_type === (new UniversalCreditManualEntry)->getMorphClass() ? 'manual' : 'posted_cash_source'];
        }

        $transaction = BankTransaction::withoutGlobalScopes()
            ->where('business_id', $item->business_id)->find($item->source_id);

        return [
            'state' => $transaction?->reconciliation_status ?? 'source_not_retained',
            'transaction_amount' => $transaction?->amount,
            'unreconciled_amount' => data_get($item->source_context, 'unreconciled_original_amount'),
        ];
    }
}
