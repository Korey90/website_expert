<?php

namespace App\Policies;

use App\Models\AccountingPeriod;
use App\Models\User;
use App\Support\PermissionHelper;

class AccountingPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, AccountingPeriod $period): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentBusiness($period);
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_finance');
    }

    public function update(User $user, AccountingPeriod $period): bool
    {
        return $this->create($user)
            && $period->status === AccountingPeriod::STATUS_OPEN
            && $this->belongsToCurrentBusiness($period);
    }

    public function transition(User $user, AccountingPeriod $period): bool
    {
        return PermissionHelper::allows($user, 'close_finance_periods')
            && $this->belongsToCurrentBusiness($period);
    }

    public function delete(User $user, AccountingPeriod $period): bool
    {
        return false;
    }

    private function belongsToCurrentBusiness(AccountingPeriod $period): bool
    {
        return currentBusiness()?->id === $period->business_id;
    }
}
