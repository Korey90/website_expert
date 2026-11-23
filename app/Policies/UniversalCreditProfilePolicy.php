<?php

namespace App\Policies;

use App\Models\UniversalCreditProfile;
use App\Models\User;
use App\Support\PermissionHelper;

class UniversalCreditProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_universal_credit_reporting');
    }

    public function view(User $user, UniversalCreditProfile $profile): bool
    {
        return $this->viewAny($user) && currentBusiness()?->id === $profile->business_id;
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_universal_credit_reporting');
    }

    public function update(User $user, UniversalCreditProfile $profile): bool
    {
        return $this->create($user) && currentBusiness()?->id === $profile->business_id;
    }

    public function delete(User $user, UniversalCreditProfile $profile): bool
    {
        return false;
    }
}
