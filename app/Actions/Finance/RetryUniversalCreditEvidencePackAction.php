<?php

namespace App\Actions\Finance;

use App\Jobs\GenerateUniversalCreditEvidencePackJob;
use App\Models\UniversalCreditEvidencePack;
use App\Models\User;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class RetryUniversalCreditEvidencePackAction
{
    public function execute(UniversalCreditEvidencePack $pack, User $actor): UniversalCreditEvidencePack
    {
        $this->authorize($pack, $actor);

        $retried = DB::transaction(function () use ($pack, $actor): UniversalCreditEvidencePack {
            $locked = UniversalCreditEvidencePack::withoutGlobalScopes()
                ->where('business_id', $pack->business_id)->whereKey($pack->id)
                ->lockForUpdate()->firstOrFail();
            $this->authorize($locked, $actor);

            if ($locked->status !== UniversalCreditEvidencePack::STATUS_FAILED) {
                throw new DomainException(__('finance.universal_credit.pack.errors.retry_failed_only'));
            }

            if ($locked->attempt_count >= (int) config('finance.universal_credit.evidence_pack.max_attempts', 6)) {
                throw new DomainException(__('finance.universal_credit.pack.errors.retry_limit'));
            }

            $locked->update([
                'status' => UniversalCreditEvidencePack::STATUS_PENDING,
                'last_error_code' => null,
                'failed_at' => null,
            ]);

            return $locked->fresh();
        }, 3);

        GenerateUniversalCreditEvidencePackJob::dispatch($retried->id)
            ->onQueue((string) config('finance.universal_credit.evidence_pack.queue', 'default'));

        return $retried;
    }

    private function authorize(UniversalCreditEvidencePack $pack, User $actor): void
    {
        $member = $actor->businessMemberships()->where('business_id', $pack->business_id)
            ->where('is_active', true)->exists();

        if (! $member || ! PermissionHelper::allows($actor, 'export_universal_credit_pack')) {
            throw new DomainException(__('finance.universal_credit.errors.period_export_denied'));
        }
    }
}
