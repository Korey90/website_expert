<?php

namespace App\Policies;

use App\Models\UniversalCreditReportItem;
use App\Models\User;
use App\Support\PermissionHelper;

class UniversalCreditReportItemPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_universal_credit_reporting');
    }

    public function view(User $user, UniversalCreditReportItem $item): bool
    {
        return $this->viewAny($user) && currentBusiness()?->id === $item->business_id;
    }

    public function update(User $user, UniversalCreditReportItem $item): bool
    {
        return PermissionHelper::allows($user, 'manage_universal_credit_reporting')
            && currentBusiness()?->id === $item->business_id;
    }

    public function delete(User $user, UniversalCreditReportItem $item): bool
    {
        return false;
    }
}
