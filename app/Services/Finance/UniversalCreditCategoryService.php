<?php

namespace App\Services\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditReportItem;
use DomainException;

class UniversalCreditCategoryService
{
    public function assertAllowed(
        UniversalCreditAssessmentPeriod $period,
        string $direction,
        string $category,
    ): void {
        $ruleKey = match ($direction) {
            UniversalCreditReportItem::DIRECTION_INCOME => 'income_categories',
            UniversalCreditReportItem::DIRECTION_EXPENSE => 'expense_categories',
            default => throw new DomainException(__('finance.universal_credit.errors.invalid_report_item')),
        };
        $categories = data_get($period->policy_rules_snapshot, $ruleKey, []);

        if (! is_array($categories) || ! in_array($category, $categories, true)) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_category'));
        }
    }
}
