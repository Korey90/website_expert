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
        return $this->canAccessTenant($user, $briefing->business_id)
            && PermissionHelper::allows($user, 'view_briefings');
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'create_briefings');
    }

    public function update(User $user, Briefing $briefing): bool
    {
        if (! $this->canAccessTenant($user, $briefing->business_id) || ! $briefing->isEditable()) {
            return false;
        }

        return PermissionHelper::allows($user, 'edit_briefings');
    }

    public function delete(User $user, Briefing $briefing): bool
    {
        return $this->canAccessTenant($user, $briefing->business_id)
            && PermissionHelper::allows($user, 'delete_briefings');
    }

    public function shareWithClient(User $user, Briefing $briefing): bool
    {
        return $this->canAccessTenant($user, $briefing->business_id)
            && PermissionHelper::allows($user, 'share_briefings')
            && $briefing->isEditable();
    }

    private function canAccessTenant(User $user, ?string $businessId): bool
    {
        return $businessId === currentBusiness()?->id
            || PermissionHelper::allows($user, 'view_businesses');
    }
}
