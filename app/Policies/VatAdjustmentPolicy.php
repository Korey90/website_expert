<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VatAdjustment;
use App\Support\PermissionHelper;

class VatAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, VatAdjustment $adjustment): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentBusiness($adjustment);
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_finance');
    }

    public function update(User $user, VatAdjustment $adjustment): bool
    {
        return $this->create($user)
            && $adjustment->status === VatAdjustment::STATUS_DRAFT
            && $this->belongsToCurrentBusiness($adjustment);
    }

    public function approve(User $user, VatAdjustment $adjustment): bool
    {
        return PermissionHelper::allows($user, 'approve_finance')
            && $adjustment->status === VatAdjustment::STATUS_DRAFT
            && $this->belongsToCurrentBusiness($adjustment);
    }

    public function post(User $user, VatAdjustment $adjustment): bool
    {
        return PermissionHelper::allows($user, 'post_finance')
            && $adjustment->status === VatAdjustment::STATUS_APPROVED
            && $this->belongsToCurrentBusiness($adjustment);
    }

    public function delete(User $user, VatAdjustment $adjustment): bool
    {
        return false;
    }

    private function belongsToCurrentBusiness(VatAdjustment $adjustment): bool
    {
        return currentBusiness()?->id === $adjustment->business_id;
    }
}
