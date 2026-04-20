<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Support\PermissionHelper;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_leads');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'create_leads')
            || PermissionHelper::allows($user, 'manage_leads');
    }

    public function update(User $user, Lead $lead): bool
    {
        return PermissionHelper::allows($user, 'edit_leads')
            || PermissionHelper::allows($user, 'manage_leads');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return PermissionHelper::allows($user, 'delete_leads');
    }

    public function export(User $user): bool
    {
        return PermissionHelper::allows($user, 'export_leads');
    }
}
