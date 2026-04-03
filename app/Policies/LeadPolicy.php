<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_leads') || $user->hasAnyRole(['admin', 'manager', 'developer']);
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_leads') || $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('manage_leads') || $user->hasAnyRole(['admin', 'manager']);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('delete_leads') || $user->hasRole('admin');
    }

    public function export(User $user): bool
    {
        return $user->can('export_leads') || $user->hasRole('admin');
    }
}
