<?php

namespace App\Actions\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\User;
use App\Services\Finance\UniversalCreditPreflightService;
use App\Services\Finance\UniversalCreditReviewSnapshotService;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReviewUniversalCreditAssessmentPeriodAction
{
    public function __construct(
        private readonly UniversalCreditPreflightService $preflight,
        private readonly UniversalCreditReviewSnapshotService $snapshotService,
    ) {}

    public function execute(
        UniversalCreditAssessmentPeriod $period,
        User $actor,
        bool $confirmed,
        bool $warningsConfirmed = false,
        ?string $notes = null,
    ): UniversalCreditAssessmentPeriod {
        $this->authorize($period, $actor);
        $notes = trim((string) $notes);

        if (! $confirmed) {
            throw new DomainException(__('finance.universal_credit.errors.review_confirmation_required'));
        }

        if (mb_strlen($notes) > 2000) {
            throw new DomainException(__('finance.universal_credit.errors.review_notes_too_long'));
        }

        return DB::transaction(function () use ($period, $actor, $warningsConfirmed, $notes): UniversalCreditAssessmentPeriod {
            $locked = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $period->business_id)->whereKey($period->id)
                ->lockForUpdate()->firstOrFail();
            $this->authorize($locked, $actor);

            if ($locked->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT) {
                throw new DomainException(__('finance.universal_credit.errors.period_not_draft'));
            }

            $result = $this->preflight->inspect($locked);
            if (! $result->isReady()) {
                throw new DomainException(__('finance.universal_credit.errors.preflight_failed'));
            }

            if ($result->warnings !== [] && ! $warningsConfirmed) {
                throw new DomainException(__('finance.universal_credit.errors.preflight_warnings_confirmation_required'));
            }

            $snapshot = $this->snapshotService->build($locked, $result);
            $locked->update([
                'status' => UniversalCreditAssessmentPeriod::STATUS_IN_REVIEW,
                'review_snapshot_version' => UniversalCreditReviewSnapshotService::VERSION,
                'review_snapshot' => $snapshot,
                'review_snapshot_hash' => $this->snapshotService->hash($snapshot),
                'preflight_warning_count' => count($result->warnings),
                'review_confirmation_version' => 'uc-review-v1',
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_notes' => $notes !== '' ? $notes : null,
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
