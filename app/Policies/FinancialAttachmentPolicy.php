<?php

namespace App\Policies;

use App\Models\FinancialAttachment;
use App\Models\User;
use App\Support\PermissionHelper;

class FinancialAttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, FinancialAttachment $attachment): bool
    {
        return $this->viewAny($user)
            && currentBusiness()?->id === $attachment->business_id;
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_finance');
    }

    public function delete(User $user, FinancialAttachment $attachment): bool
    {
        return false;
    }
}
