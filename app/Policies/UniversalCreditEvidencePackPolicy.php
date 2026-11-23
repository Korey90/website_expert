<?php

namespace App\Policies;

use App\Models\UniversalCreditEvidencePack;
use App\Models\User;
use App\Support\PermissionHelper;

class UniversalCreditEvidencePackPolicy
{
    public function view(User $user, UniversalCreditEvidencePack $pack): bool
    {
        return PermissionHelper::allows($user, 'view_universal_credit_reporting')
            && $this->belongsToCurrentBusiness($pack);
    }

    public function download(User $user, UniversalCreditEvidencePack $pack): bool
    {
        return PermissionHelper::allows($user, 'export_universal_credit_pack')
            && $this->belongsToCurrentBusiness($pack);
    }

    public function retry(User $user, UniversalCreditEvidencePack $pack): bool
    {
        return $this->download($user, $pack);
    }

    private function belongsToCurrentBusiness(UniversalCreditEvidencePack $pack): bool
    {
        return currentBusiness()?->id === $pack->business_id;
    }
}
