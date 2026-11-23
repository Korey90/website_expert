<?php

namespace App\Actions\Finance;

use App\Jobs\GenerateUniversalCreditEvidencePackJob;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditEvidencePack;
use App\Models\User;
use App\Services\Finance\UniversalCreditCalculationHashService;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class RequestUniversalCreditEvidencePackAction
{
    public function __construct(
        private readonly UniversalCreditCalculationHashService $hasher,
    ) {}

    public function execute(
        UniversalCreditAssessmentPeriod $period,
        User $actor,
        ?string $locale = null,
    ): UniversalCreditEvidencePack {
        $this->authorize($period, $actor);
        $locale = in_array($locale, ['en', 'pl', 'pt'], true) ? $locale : app()->getLocale();
        $locale = in_array($locale, ['en', 'pl', 'pt'], true) ? $locale : 'en';

        $pack = DB::transaction(function () use ($period, $actor, $locale): UniversalCreditEvidencePack {
            $locked = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $period->business_id)->whereKey($period->id)
                ->lockForUpdate()->firstOrFail();
            $this->authorize($locked, $actor);

            if (! in_array($locked->status, [
                UniversalCreditAssessmentPeriod::STATUS_LOCKED,
                UniversalCreditAssessmentPeriod::STATUS_REPORTED,
                UniversalCreditAssessmentPeriod::STATUS_SUPERSEDED,
            ], true) || $locked->locked_at === null) {
                throw new DomainException(__('finance.universal_credit.pack.errors.locked_only'));
            }

            if (! is_array($locked->review_snapshot)
                || blank($locked->review_snapshot_hash)
                || ! hash_equals((string) $locked->review_snapshot_hash, $this->hasher->hash($locked->review_snapshot))) {
                throw new DomainException(__('finance.universal_credit.pack.errors.snapshot_integrity'));
            }

            $existing = UniversalCreditEvidencePack::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('assessment_period_id', $locked->id)
                ->where('snapshot_hash', $locked->review_snapshot_hash)
                ->where(function ($query): void {
                    $query->whereIn('status', [
                        UniversalCreditEvidencePack::STATUS_PENDING,
                        UniversalCreditEvidencePack::STATUS_PROCESSING,
                    ])->orWhere(function ($complete): void {
                        $complete->where('status', UniversalCreditEvidencePack::STATUS_COMPLETED)
                            ->where('expires_at', '>', now());
                    })->orWhere('status', UniversalCreditEvidencePack::STATUS_FAILED);
                })
                ->latest('version')
                ->first();

            if ($existing) {
                return $existing;
            }

            $version = (int) UniversalCreditEvidencePack::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('assessment_period_id', $locked->id)
                ->max('version') + 1;

            return UniversalCreditEvidencePack::withoutGlobalScopes()->create([
                'business_id' => $locked->business_id,
                'assessment_period_id' => $locked->id,
                'version' => $version,
                'status' => UniversalCreditEvidencePack::STATUS_PENDING,
                'snapshot_hash' => $locked->review_snapshot_hash,
                'locale' => $locale,
                'requested_by' => $actor->id,
            ]);
        }, 3);

        if ($pack->status === UniversalCreditEvidencePack::STATUS_PENDING && $pack->wasRecentlyCreated) {
            GenerateUniversalCreditEvidencePackJob::dispatch($pack->id)
                ->onQueue((string) config('finance.universal_credit.evidence_pack.queue', 'default'));
        }

        return $pack;
    }

    private function authorize(UniversalCreditAssessmentPeriod $period, User $actor): void
    {
        $member = $actor->businessMemberships()->where('business_id', $period->business_id)
            ->where('is_active', true)->exists();

        if (! $member || ! PermissionHelper::allows($actor, 'export_universal_credit_pack')) {
            throw new DomainException(__('finance.universal_credit.errors.period_export_denied'));
        }
    }
}
