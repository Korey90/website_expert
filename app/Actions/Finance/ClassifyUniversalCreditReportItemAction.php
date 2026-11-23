<?php

namespace App\Actions\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditReportItem;
use App\Models\User;
use App\Services\Finance\UniversalCreditCategoryService;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class ClassifyUniversalCreditReportItemAction
{
    public function __construct(private readonly UniversalCreditCategoryService $categories) {}

    public function execute(
        UniversalCreditReportItem $item,
        User $actor,
        string $classificationStatus,
        string $category,
        int|float|string $businessUsePercentage = 100,
        ?string $businessUseReason = null,
        ?string $classificationReason = null,
    ): UniversalCreditReportItem {
        $this->authorize($item, $actor);
        $basisPoints = $this->basisPoints($businessUsePercentage);
        $businessUseReason = $this->optionalText($businessUseReason, 2000);
        $classificationReason = $this->optionalText($classificationReason, 2000);

        return DB::transaction(function () use (
            $item,
            $actor,
            $classificationStatus,
            $category,
            $basisPoints,
            $businessUseReason,
            $classificationReason,
        ): UniversalCreditReportItem {
            $period = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $item->business_id)
                ->whereKey($item->assessment_period_id)
                ->lockForUpdate()
                ->firstOrFail();
            $locked = UniversalCreditReportItem::withoutGlobalScopes()
                ->where('business_id', $period->business_id)
                ->where('assessment_period_id', $period->id)
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorize($locked, $actor);

            if ($period->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT) {
                throw new DomainException(__('finance.universal_credit.errors.period_not_draft'));
            }

            if (! $locked->source_is_current) {
                throw new DomainException(__('finance.universal_credit.errors.source_not_current'));
            }

            if (! in_array($classificationStatus, [
                UniversalCreditReportItem::STATUS_INCLUDED,
                UniversalCreditReportItem::STATUS_EXCLUDED,
            ], true)) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_classification'));
            }

            $this->categories->assertAllowed($period, $locked->direction, $category);

            if ($category === 'review_required') {
                throw new DomainException(__('finance.universal_credit.errors.review_category_not_final'));
            }

            if ($classificationStatus === UniversalCreditReportItem::STATUS_INCLUDED) {
                if ($basisPoints === 0) {
                    throw new DomainException(__('finance.universal_credit.errors.invalid_business_use'));
                }

                if ($locked->gross_amount_pence === null) {
                    throw new DomainException(__('finance.universal_credit.errors.gbp_snapshot_required'));
                }
            }

            if ($locked->direction === UniversalCreditReportItem::DIRECTION_INCOME && $basisPoints !== 10000) {
                throw new DomainException(__('finance.universal_credit.errors.income_business_use'));
            }

            if ($classificationStatus === UniversalCreditReportItem::STATUS_INCLUDED
                && $locked->direction === UniversalCreditReportItem::DIRECTION_EXPENSE
                && $basisPoints < 10000
                && $businessUseReason === null) {
                throw new DomainException(__('finance.universal_credit.errors.business_use_reason_required'));
            }

            if ($classificationStatus === UniversalCreditReportItem::STATUS_EXCLUDED
                && $classificationReason === null) {
                throw new DomainException(__('finance.universal_credit.errors.classification_reason_required'));
            }

            $manualMorph = (new UniversalCreditManualEntry)->getMorphClass();
            $locked->update([
                'category' => $category,
                'classification_status' => $classificationStatus,
                'business_use_basis_points' => $classificationStatus === UniversalCreditReportItem::STATUS_EXCLUDED ? 0 : $basisPoints,
                'business_use_reason' => $classificationStatus === UniversalCreditReportItem::STATUS_INCLUDED ? $businessUseReason : null,
                'classification_reason' => $classificationReason,
                'calculation_method' => $locked->source_type === $manualMorph
                    ? UniversalCreditReportItem::METHOD_MANUAL
                    : ($basisPoints < 10000
                        ? UniversalCreditReportItem::METHOD_BUSINESS_USE
                        : UniversalCreditReportItem::METHOD_ACTUAL),
                'business_share_pence' => null,
                'reportable_amount_pence' => null,
                'source_change_reason' => null,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            return $locked->fresh();
        }, 3);
    }

    private function basisPoints(int|float|string $percentage): int
    {
        $value = trim((string) $percentage);

        if (! preg_match('/^(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)$/', $value)) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_business_use'));
        }

        return (int) round((float) $value * 100);
    }

    private function optionalText(?string $value, int $maximum): ?string
    {
        $value = trim((string) $value);

        if (mb_strlen($value) > $maximum) {
            throw new DomainException(__('finance.universal_credit.errors.text_too_long'));
        }

        return $value !== '' ? $value : null;
    }

    private function authorize(UniversalCreditReportItem $item, User $actor): void
    {
        $isMember = $actor->businessMemberships()
            ->where('business_id', $item->business_id)
            ->where('is_active', true)
            ->exists();

        if (! $isMember || ! PermissionHelper::allows($actor, 'manage_universal_credit_reporting')) {
            throw new DomainException(__('finance.universal_credit.errors.period_manage_denied'));
        }
    }
}
