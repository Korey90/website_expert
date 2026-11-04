<?php

namespace App\Policies;

use App\Models\LedgerAccount;
use App\Models\User;
use App\Support\PermissionHelper;

class LedgerAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, LedgerAccount $account): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentBusiness($account);
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_finance');
    }

    public function update(User $user, LedgerAccount $account): bool
    {
        return $this->create($user) && $this->belongsToCurrentBusiness($account);
    }

    public function delete(User $user, LedgerAccount $account): bool
    {
        return $this->update($user, $account) && ! $account->is_system && ! $account->isReferenced();
    }

    private function belongsToCurrentBusiness(LedgerAccount $account): bool
    {
        return currentBusiness()?->id === $account->business_id;
    }
}
