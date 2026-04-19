<?php

namespace App\Policies;

use App\Models\BriefingTemplate;
use App\Models\User;

class BriefingTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'developer', 'super_admin']);
    }

    public function view(User $user, BriefingTemplate $template): bool
    {
        return $user->hasRole('super_admin')
            || $user->hasAnyRole(['admin', 'manager', 'developer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'super_admin']);
    }

    public function update(User $user, BriefingTemplate $template): bool
    {
        if ($template->isGlobal()) {
            return $user->hasRole('super_admin');
        }

        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function delete(User $user, BriefingTemplate $template): bool
    {
        if ($template->isGlobal()) {
            return $user->hasRole('super_admin');
        }

        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
