<?php

namespace App\Policies;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\User;
use App\Support\PermissionHelper;

class UniversalCreditAssessmentPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_universal_credit_reporting');
    }

    public function view(User $user, UniversalCreditAssessmentPeriod $period): bool
    {
        return $this->viewAny($user) && $this->belongsToCurrentBusiness($period);
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_universal_credit_reporting');
    }

    public function manage(User $user, UniversalCreditAssessmentPeriod $period): bool
    {
        return $this->create($user) && $this->belongsToCurrentBusiness($period);
    }

    public function export(User $user, UniversalCreditAssessmentPeriod $period): bool
    {
        return PermissionHelper::allows($user, 'export_universal_credit_pack')
            && $this->belongsToCurrentBusiness($period);
    }

    public function update(User $user, UniversalCreditAssessmentPeriod $period): bool
    {
        return false;
    }

    public function delete(User $user, UniversalCreditAssessmentPeriod $period): bool
    {
        return false;
    }

    private function belongsToCurrentBusiness(UniversalCreditAssessmentPeriod $period): bool
    {
        return currentBusiness()?->id === $period->business_id;
    }
}
