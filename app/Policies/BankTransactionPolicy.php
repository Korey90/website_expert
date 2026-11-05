<?php

namespace App\Policies;

use App\Models\BankTransaction;
use App\Models\User;
use App\Support\PermissionHelper;

class BankTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, BankTransaction $transaction): bool
    {
        return $this->viewAny($user) && currentBusiness()?->id === $transaction->business_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, BankTransaction $transaction): bool
    {
        return false;
    }

    public function delete(User $user, BankTransaction $transaction): bool
    {
        return false;
    }
}
