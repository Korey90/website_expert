<?php

namespace App\Policies;

use App\Models\Briefing;
use App\Models\User;
use App\Support\PermissionHelper;

class BriefingPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_briefings');
    }

    public function view(User $user, Briefing $briefing): bool
    {
        return PermissionHelper::allows($user, 'view_briefings');
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'create_briefings');
    }

    public function update(User $user, Briefing $briefing): bool
    {
        if (!$briefing->isEditable()) {
            return false;
        }

        return PermissionHelper::allows($user, 'edit_briefings');
    }

    public function delete(User $user, Briefing $briefing): bool
    {
        return PermissionHelper::allows($user, 'delete_briefings');
    }

    public function shareWithClient(User $user, Briefing $briefing): bool
    {
        return PermissionHelper::allows($user, 'share_briefings')
            && $briefing->isEditable();
    }
}
