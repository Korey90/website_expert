<?php

namespace App\Policies;

use App\Models\RecurringExpenseTemplate;
use App\Models\User;
use App\Support\PermissionHelper;

class RecurringExpenseTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, RecurringExpenseTemplate $template): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentBusiness($template);
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_finance');
    }

    public function update(User $user, RecurringExpenseTemplate $template): bool
    {
        return $this->create($user) && $this->belongsToCurrentBusiness($template);
    }

    public function delete(User $user, RecurringExpenseTemplate $template): bool
    {
        return $this->update($user, $template) && $template->generatedExpenses()->doesntExist();
    }

    public function restore(User $user, RecurringExpenseTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    public function forceDelete(User $user, RecurringExpenseTemplate $template): bool
    {
        return false;
    }

    private function belongsToCurrentBusiness(RecurringExpenseTemplate $template): bool
    {
        return currentBusiness()?->id === $template->business_id;
    }
}
