<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\Finance\VatReturnSnapshotService;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class LockVatReturnAction
{
    public function __construct(private readonly VatReturnSnapshotService $snapshotService) {}

    public function execute(VatReturn $return, User $actor, string $reason): VatReturn
    {
        return DB::transaction(function () use ($return, $actor, $reason): VatReturn {
            Business::query()->lockForUpdate()->findOrFail($return->business_id);
            $locked = VatReturn::withoutGlobalScopes()->lockForUpdate()->findOrFail($return->id);
            $this->assertAuthorized($locked, $actor);
            $reason = trim($reason);

            if (mb_strlen($reason) < 10 || mb_strlen($reason) > 2000) {
                throw new DomainException(__('finance.vat_returns.errors.lock_reason'));
            }

            $current = $this->snapshotService->build(
                $locked->business_id,
                $locked->period_from->toDateString(),
                $locked->period_to->toDateString(),
            );

            if (! hash_equals($locked->snapshot_hash, $current['hash'])) {
                throw new DomainException(__('finance.vat_returns.errors.snapshot_changed_lock'));
            }

            $locked->update([
                'status' => VatReturn::STATUS_LOCKED,
                'locked_by' => $actor->id,
                'locked_at' => now(),
                'lock_reason' => $reason,
            ]);

            return $locked->fresh(['createdBy', 'reviewedBy', 'lockedBy']);
        }, 3);
    }

    private function assertAuthorized(VatReturn $return, User $actor): void
    {
        if ($return->status !== VatReturn::STATUS_REVIEWED) {
            throw new DomainException(__('finance.vat_returns.errors.reviewed_only_lock'));
        }

        if (! $actor->businessMemberships()->where('business_id', $return->business_id)->where('is_active', true)->exists()) {
            throw new DomainException(__('finance.vat_returns.errors.not_member'));
        }

        if (! PermissionHelper::allows($actor, 'close_finance_periods')) {
            throw new DomainException(__('finance.vat_returns.errors.cannot_lock'));
        }
    }
}
