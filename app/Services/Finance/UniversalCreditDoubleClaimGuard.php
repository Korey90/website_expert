<?php

namespace App\Services\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditReportItem;
use App\Models\UniversalCreditSimplifiedExpense;
use DomainException;

class UniversalCreditDoubleClaimGuard
{
    public function assertCanCreateSimplified(
        UniversalCreditAssessmentPeriod $period,
        string $type,
    ): void {
        if (UniversalCreditReportItem::withoutGlobalScopes()
            ->where('business_id', $period->business_id)
            ->where('assessment_period_id', $period->id)
            ->where('direction', UniversalCreditReportItem::DIRECTION_EXPENSE)
            ->where('classification_status', UniversalCreditReportItem::STATUS_INCLUDED)
            ->where('source_is_current', true)
            ->whereIn('category', $this->conflictingCategories($type))
            ->exists()) {
            throw new DomainException(__('finance.universal_credit.errors.simplified_double_claim'));
        }
    }

    public function assertCanClassify(
        UniversalCreditReportItem $item,
        string $classificationStatus,
        string $category,
    ): void {
        if ($classificationStatus !== UniversalCreditReportItem::STATUS_INCLUDED
            || $item->direction !== UniversalCreditReportItem::DIRECTION_EXPENSE) {
            return;
        }

        foreach (UniversalCreditSimplifiedExpense::types() as $type) {
            if (! in_array($category, $this->conflictingCategories($type), true)) {
                continue;
            }

            if (UniversalCreditSimplifiedExpense::withoutGlobalScopes()
                ->where('business_id', $item->business_id)
                ->where('assessment_period_id', $item->assessment_period_id)
                ->where('type', $type)
                ->where('status', UniversalCreditSimplifiedExpense::STATUS_ACTIVE)
                ->exists()) {
                throw new DomainException(__('finance.universal_credit.errors.simplified_double_claim'));
            }
        }
    }

    public function assertPeriod(UniversalCreditAssessmentPeriod $period): void
    {
        $types = UniversalCreditSimplifiedExpense::withoutGlobalScopes()
            ->where('business_id', $period->business_id)
            ->where('assessment_period_id', $period->id)
            ->where('status', UniversalCreditSimplifiedExpense::STATUS_ACTIVE)
            ->pluck('type');

        foreach ($types as $type) {
            $this->assertCanCreateSimplified($period, $type);
        }
    }

    /** @return list<string> */
    public function conflictingCategories(string $type): array
    {
        return match ($type) {
            UniversalCreditSimplifiedExpense::TYPE_VEHICLE_MILEAGE => [
                'car_or_minicab_simplified_travel',
                'other_vehicle_and_business_travel',
            ],
            UniversalCreditSimplifiedExpense::TYPE_HOME_WORKING => ['use_of_home'],
            UniversalCreditSimplifiedExpense::TYPE_BUSINESS_PREMISES_LIVING => ['living_at_business_premises'],
            default => throw new DomainException(__('finance.universal_credit.errors.invalid_simplified_expense')),
        };
    }
}
