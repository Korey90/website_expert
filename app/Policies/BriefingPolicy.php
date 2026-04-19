<?php

namespace App\Policies;

use App\Models\Briefing;
use App\Models\User;

class BriefingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'developer', 'super_admin']);
    }

    public function view(User $user, Briefing $briefing): bool
    {
        return $user->hasRole('super_admin')
            || $user->hasAnyRole(['admin', 'manager', 'developer']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'super_admin']);
    }

    public function update(User $user, Briefing $briefing): bool
    {
        if (!$briefing->isEditable()) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'manager', 'super_admin']);
    }

    public function delete(User $user, Briefing $briefing): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function shareWithClient(User $user, Briefing $briefing): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'super_admin'])
            && $briefing->isEditable();
    }
}
