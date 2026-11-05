<?php

namespace App\Policies;

use App\Models\BankAccount;
use App\Models\User;
use App\Support\PermissionHelper;

class BankAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, BankAccount $account): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentBusiness($account);
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_finance');
    }

    public function update(User $user, BankAccount $account): bool
    {
        return $this->create($user) && $this->belongsToCurrentBusiness($account);
    }

    public function delete(User $user, BankAccount $account): bool
    {
        return false;
    }

    private function belongsToCurrentBusiness(BankAccount $account): bool
    {
        return currentBusiness()?->id === $account->business_id;
    }
}
