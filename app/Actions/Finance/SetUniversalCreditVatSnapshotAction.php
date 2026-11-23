<?php

namespace App\Actions\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditReportItem;
use App\Models\User;
use App\Services\Finance\UniversalCreditCalculationInvalidator;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class SetUniversalCreditVatSnapshotAction
{
    public function __construct(private readonly UniversalCreditCalculationInvalidator $invalidator) {}

    public function execute(
        UniversalCreditReportItem $item,
        User $actor,
        int|float|string $vatAmountGbp,
        string $reason,
    ): UniversalCreditReportItem {
        $this->authorize($item, $actor);
        $vatPence = $this->moneyPence($vatAmountGbp);
        $reason = trim($reason);

        if (mb_strlen($reason) < 3 || mb_strlen($reason) > 2000) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_vat_reason'));
        }

        return DB::transaction(function () use ($item, $actor, $vatPence, $reason): UniversalCreditReportItem {
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

            if ($locked->gross_amount_pence === null || $vatPence > $locked->gross_amount_pence) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_vat_snapshot'));
            }

            $locked->update([
                'vat_amount_pence' => $vatPence,
                'vat_snapshot_source' => 'manual',
                'vat_snapshot_reason' => $reason,
                'business_share_pence' => null,
                'reportable_amount_pence' => null,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);
            $this->invalidator->invalidate($period);

            return $locked->fresh();
        }, 3);
    }

    private function moneyPence(int|float|string $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $value)) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_vat_snapshot'));
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
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
