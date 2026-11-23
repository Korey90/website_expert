<?php

namespace App\Actions\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\User;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class SupersedeUniversalCreditAssessmentPeriodAction
{
    public function execute(
        UniversalCreditAssessmentPeriod $period,
        User $actor,
        string $reason,
        ?UniversalCreditAssessmentPeriod $supersedingPeriod = null,
    ): UniversalCreditAssessmentPeriod {
        $this->authorize($period, $actor);
        $reason = trim($reason);

        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 2000) {
            throw new DomainException(__('finance.universal_credit.errors.supersede_reason_invalid'));
        }

        if ($supersedingPeriod !== null
            && ($supersedingPeriod->business_id !== $period->business_id
                || $supersedingPeriod->date_from->ne($period->date_from)
                || $supersedingPeriod->date_to->ne($period->date_to)
                || $supersedingPeriod->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT)) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_superseding_period'));
        }

        return DB::transaction(function () use ($period, $actor, $reason, $supersedingPeriod): UniversalCreditAssessmentPeriod {
            $locked = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $period->business_id)->whereKey($period->id)
                ->lockForUpdate()->firstOrFail();
            $this->authorize($locked, $actor);

            if ($locked->status === UniversalCreditAssessmentPeriod::STATUS_SUPERSEDED) {
                throw new DomainException(__('finance.universal_credit.errors.period_already_superseded'));
            }

            $locked->update([
                'status' => UniversalCreditAssessmentPeriod::STATUS_SUPERSEDED,
                'superseded_by_user_id' => $actor->id,
                'superseding_period_id' => $supersedingPeriod?->id,
                'superseded_at' => now(),
                'supersede_reason' => $reason,
            ]);

            return $locked->fresh();
        }, 3);
    }

    private function authorize(UniversalCreditAssessmentPeriod $period, User $actor): void
    {
        $member = $actor->businessMemberships()->where('business_id', $period->business_id)
            ->where('is_active', true)->exists();

        if (! $member || ! PermissionHelper::allows($actor, 'manage_universal_credit_reporting')) {
            throw new DomainException(__('finance.universal_credit.errors.period_manage_denied'));
        }
    }
}
