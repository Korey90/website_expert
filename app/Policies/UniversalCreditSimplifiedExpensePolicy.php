<?php

namespace App\Policies;

use App\Models\UniversalCreditSimplifiedExpense;
use App\Models\User;
use App\Support\PermissionHelper;

class UniversalCreditSimplifiedExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_universal_credit_reporting');
    }

    public function view(User $user, UniversalCreditSimplifiedExpense $expense): bool
    {
        return $this->viewAny($user) && currentBusiness()?->id === $expense->business_id;
    }

    public function update(User $user, UniversalCreditSimplifiedExpense $expense): bool
    {
        return PermissionHelper::allows($user, 'manage_universal_credit_reporting')
            && currentBusiness()?->id === $expense->business_id;
    }

    public function delete(User $user, UniversalCreditSimplifiedExpense $expense): bool
    {
        return false;
    }
}
