<?php

namespace App\Services\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditReportItem;
use App\Models\UniversalCreditSimplifiedExpense;
use Illuminate\Support\Collection;

class UniversalCreditAssessmentPeriodSnapshotService
{
    public function __construct(
        private readonly UniversalCreditCalculationHashService $hasher,
    ) {}

    /**
     * @param  Collection<int, UniversalCreditReportItem>  $items
     * @param  Collection<int, UniversalCreditSimplifiedExpense>  $simplified
     * @return array<string, mixed>
     */
    public function build(
        UniversalCreditAssessmentPeriod $period,
        Collection $items,
        Collection $simplified,
    ): array {
        return [
            'schema_version' => 1,
            'period' => [
                'id' => $period->id,
                'business_id' => $period->business_id,
                'date_from' => $period->date_from->toDateString(),
                'date_to' => $period->date_to->toDateString(),
                'policy_rules_hash' => $period->policy_rules_hash,
                'vat_reporting_method' => $period->vat_reporting_method_snapshot,
            ],
            'items' => $items->map(fn (UniversalCreditReportItem $item): array => [
                'id' => $item->id,
                'source_identity_hash' => $item->source_identity_hash,
                'source_fingerprint' => $item->source_fingerprint,
                'source_is_current' => $item->source_is_current,
                'direction' => $item->direction,
                'category' => $item->category,
                'classification_status' => $item->classification_status,
                'cash_date' => $item->cash_date->toDateString(),
                'gross_amount_pence' => $item->gross_amount_pence,
                'gbp_exchange_rate' => $item->gbp_exchange_rate,
                'exchange_rate_source' => $item->exchange_rate_source,
                'exchange_rate_date' => $item->exchange_rate_date?->toDateString(),
                'fx_snapshot_reason' => $item->fx_snapshot_reason,
                'vat_amount_pence' => $item->vat_amount_pence,
                'vat_snapshot_source' => $item->vat_snapshot_source,
                'business_use_basis_points' => $item->business_use_basis_points,
                'calculation_method' => $item->calculation_method,
                'calculation_rule' => $item->calculation_rule,
                'business_share_pence' => $item->business_share_pence,
                'reportable_amount_pence' => $item->reportable_amount_pence,
            ])->values()->all(),
            'simplified_expenses' => $simplified->map(fn (UniversalCreditSimplifiedExpense $expense): array => [
                'id' => $expense->id,
                'type' => $expense->type,
                'status' => $expense->status,
                'inputs' => $expense->inputs,
                'calculated_amount_pence' => $expense->calculated_amount_pence,
                'policy_rules_hash' => $expense->policy_rules_hash,
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, UniversalCreditReportItem>  $items
     * @param  Collection<int, UniversalCreditSimplifiedExpense>  $simplified
     */
    public function hash(
        UniversalCreditAssessmentPeriod $period,
        Collection $items,
        Collection $simplified,
    ): string {
        return $this->hasher->hash($this->build($period, $items, $simplified));
    }
}
