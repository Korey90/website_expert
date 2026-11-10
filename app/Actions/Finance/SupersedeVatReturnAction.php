<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\User;
use App\Models\VatReturn;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class SupersedeVatReturnAction
{
    public function execute(VatReturn $return, User $actor, string $reason): VatReturn
    {
        return DB::transaction(function () use ($return, $actor, $reason): VatReturn {
            Business::query()->lockForUpdate()->findOrFail($return->business_id);
            $locked = VatReturn::withoutGlobalScopes()->lockForUpdate()->findOrFail($return->id);
            $this->assertAuthorized($locked, $actor);
            $reason = trim($reason);

            if (mb_strlen($reason) < 10 || mb_strlen($reason) > 2000) {
                throw new DomainException(__('finance.vat_returns.errors.supersede_reason'));
            }

            $locked->update([
                'status' => VatReturn::STATUS_SUPERSEDED,
                'superseded_by' => $actor->id,
                'superseded_at' => now(),
                'supersede_reason' => $reason,
            ]);

            return $locked->fresh(['supersededBy']);
        }, 3);
    }

    private function assertAuthorized(VatReturn $return, User $actor): void
    {
        if (! in_array($return->status, [VatReturn::STATUS_DRAFT, VatReturn::STATUS_REVIEWED], true)) {
            throw new DomainException(__('finance.vat_returns.errors.supersede_status'));
        }

        if (! $actor->businessMemberships()->where('business_id', $return->business_id)->where('is_active', true)->exists()) {
            throw new DomainException(__('finance.vat_returns.errors.not_member'));
        }

        $permission = $return->status === VatReturn::STATUS_REVIEWED ? 'approve_finance' : 'manage_finance';

        if (! PermissionHelper::allows($actor, $permission)) {
            throw new DomainException(__('finance.vat_returns.errors.cannot_supersede'));
        }
    }
}
