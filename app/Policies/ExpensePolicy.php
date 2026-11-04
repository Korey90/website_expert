<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Support\PermissionHelper;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentBusiness($expense);
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_finance');
    }

    public function update(User $user, Expense $expense): bool
    {
        return $this->create($user)
            && $expense->status === Expense::STATUS_DRAFT
            && $this->belongsToCurrentBusiness($expense);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->update($user, $expense);
    }

    public function approve(User $user, Expense $expense): bool
    {
        return PermissionHelper::allows($user, 'approve_finance')
            && $expense->status === Expense::STATUS_DRAFT
            && $this->belongsToCurrentBusiness($expense);
    }

    public function post(User $user, Expense $expense): bool
    {
        return PermissionHelper::allows($user, 'post_finance')
            && $expense->status === Expense::STATUS_APPROVED
            && $this->belongsToCurrentBusiness($expense);
    }

    public function void(User $user, Expense $expense): bool
    {
        return PermissionHelper::allows($user, 'manage_finance')
            && in_array($expense->status, [Expense::STATUS_DRAFT, Expense::STATUS_APPROVED], true)
            && $this->belongsToCurrentBusiness($expense);
    }

    public function restore(User $user, Expense $expense): bool
    {
        return $this->create($user)
            && $expense->status === Expense::STATUS_DRAFT
            && $this->belongsToCurrentBusiness($expense);
    }

    public function forceDelete(User $user, Expense $expense): bool
    {
        return false;
    }

    private function belongsToCurrentBusiness(Expense $expense): bool
    {
        return currentBusiness()?->id === $expense->business_id;
    }
}
