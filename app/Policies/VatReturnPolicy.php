<?php

namespace App\Policies;

use App\Models\HmrcVatSubmission;
use App\Models\User;
use App\Models\VatReturn;
use App\Support\PermissionHelper;

class VatReturnPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, VatReturn $return): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentBusiness($return);
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_finance');
    }

    public function review(User $user, VatReturn $return): bool
    {
        return PermissionHelper::allows($user, 'approve_finance')
            && $return->status === VatReturn::STATUS_DRAFT
            && $this->belongsToCurrentBusiness($return);
    }

    public function lock(User $user, VatReturn $return): bool
    {
        return PermissionHelper::allows($user, 'close_finance_periods')
            && $return->status === VatReturn::STATUS_REVIEWED
            && $this->belongsToCurrentBusiness($return);
    }

    public function supersede(User $user, VatReturn $return): bool
    {
        $permission = $return->status === VatReturn::STATUS_REVIEWED ? 'approve_finance' : 'manage_finance';

        return in_array($return->status, [VatReturn::STATUS_DRAFT, VatReturn::STATUS_REVIEWED], true)
            && PermissionHelper::allows($user, $permission)
            && $this->belongsToCurrentBusiness($return);
    }

    public function submit(User $user, VatReturn $return): bool
    {
        return PermissionHelper::allows($user, 'submit_tax_returns')
            && $return->status === VatReturn::STATUS_LOCKED
            && ! $return->hmrcSubmission()->exists()
            && $this->belongsToCurrentBusiness($return);
    }

    public function reconcile(User $user, VatReturn $return): bool
    {
        return PermissionHelper::allows($user, 'submit_tax_returns')
            && $return->hmrcSubmission()->where('status', HmrcVatSubmission::STATUS_OUTCOME_UNKNOWN)->exists()
            && $this->belongsToCurrentBusiness($return);
    }

    public function update(User $user, VatReturn $return): bool
    {
        return false;
    }

    public function delete(User $user, VatReturn $return): bool
    {
        return false;
    }

    private function belongsToCurrentBusiness(VatReturn $return): bool
    {
        return currentBusiness()?->id === $return->business_id;
    }
}
