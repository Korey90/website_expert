<?php

namespace App\Policies;

use App\Models\PurchaseCreditNote;
use App\Models\User;
use App\Support\PermissionHelper;

class PurchaseCreditNotePolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, PurchaseCreditNote $creditNote): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentBusiness($creditNote);
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_finance');
    }

    public function issue(User $user, PurchaseCreditNote $creditNote): bool
    {
        return PermissionHelper::allows($user, 'approve_finance')
            && PermissionHelper::allows($user, 'post_finance')
            && $creditNote->status === PurchaseCreditNote::STATUS_DRAFT
            && $this->belongsToCurrentBusiness($creditNote);
    }

    public function settle(User $user, PurchaseCreditNote $creditNote): bool
    {
        return PermissionHelper::allows($user, 'post_finance')
            && in_array($creditNote->status, [
                PurchaseCreditNote::STATUS_ISSUED,
                PurchaseCreditNote::STATUS_PARTIALLY_SETTLED,
            ], true)
            && $this->belongsToCurrentBusiness($creditNote);
    }

    public function void(User $user, PurchaseCreditNote $creditNote): bool
    {
        return PermissionHelper::allows($user, 'manage_finance')
            && $creditNote->status === PurchaseCreditNote::STATUS_DRAFT
            && (float) $creditNote->allocated_amount === 0.0
            && (float) $creditNote->refunded_amount === 0.0
            && $this->belongsToCurrentBusiness($creditNote);
    }

    public function update(User $user, PurchaseCreditNote $creditNote): bool
    {
        return false;
    }

    public function delete(User $user, PurchaseCreditNote $creditNote): bool
    {
        return false;
    }

    private function belongsToCurrentBusiness(PurchaseCreditNote $creditNote): bool
    {
        return currentBusiness()?->id === $creditNote->business_id;
    }
}
