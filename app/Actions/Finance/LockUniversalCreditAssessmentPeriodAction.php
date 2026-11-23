<?php

namespace App\Actions\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\User;
use App\Services\Finance\UniversalCreditPreflightService;
use App\Services\Finance\UniversalCreditReviewSnapshotService;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class LockUniversalCreditAssessmentPeriodAction
{
    public function __construct(
        private readonly UniversalCreditPreflightService $preflight,
        private readonly UniversalCreditReviewSnapshotService $snapshotService,
    ) {}

    public function execute(
        UniversalCreditAssessmentPeriod $period,
        User $actor,
        string $reason,
    ): UniversalCreditAssessmentPeriod {
        $this->authorize($period, $actor);
        $reason = trim($reason);

        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 2000) {
            throw new DomainException(__('finance.universal_credit.errors.lock_reason_invalid'));
        }

        return DB::transaction(function () use ($period, $actor, $reason): UniversalCreditAssessmentPeriod {
            $locked = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $period->business_id)->whereKey($period->id)
                ->lockForUpdate()->firstOrFail();
            $this->authorize($locked, $actor);

            if ($locked->status !== UniversalCreditAssessmentPeriod::STATUS_IN_REVIEW) {
                throw new DomainException(__('finance.universal_credit.errors.period_not_in_review'));
            }

            $result = $this->preflight->inspect($locked);
            if (! $result->isReady()) {
                throw new DomainException(__('finance.universal_credit.errors.preflight_failed'));
            }

            $snapshot = $this->snapshotService->build($locked, $result);
            $hash = $this->snapshotService->hash($snapshot);
            if (blank($locked->review_snapshot_hash)
                || ! hash_equals((string) $locked->review_snapshot_hash, $hash)) {
                throw new DomainException(__('finance.universal_credit.errors.review_snapshot_changed'));
            }

            $locked->update([
                'status' => UniversalCreditAssessmentPeriod::STATUS_LOCKED,
                'locked_by' => $actor->id,
                'locked_at' => now(),
                'lock_reason' => $reason,
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
