<?php

namespace App\Policies;

use App\Models\UniversalCreditManualEntry;
use App\Models\User;
use App\Support\PermissionHelper;

class UniversalCreditManualEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_universal_credit_reporting');
    }

    public function view(User $user, UniversalCreditManualEntry $entry): bool
    {
        return $this->viewAny($user) && currentBusiness()?->id === $entry->business_id;
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_universal_credit_reporting');
    }

    public function update(User $user, UniversalCreditManualEntry $entry): bool
    {
        return $this->create($user) && currentBusiness()?->id === $entry->business_id;
    }

    public function delete(User $user, UniversalCreditManualEntry $entry): bool
    {
        return false;
    }
}
