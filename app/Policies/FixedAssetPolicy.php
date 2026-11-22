<?php

namespace App\Policies;

use App\Models\FixedAsset;
use App\Models\User;
use App\Support\PermissionHelper;

class FixedAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, FixedAsset $asset): bool
    {
        return $this->viewAny($user) && currentBusiness()?->id === $asset->business_id;
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_finance');
    }

    public function update(User $user, FixedAsset $asset): bool
    {
        return $this->create($user)
            && $asset->status === FixedAsset::STATUS_DRAFT
            && currentBusiness()?->id === $asset->business_id;
    }

    public function delete(User $user, FixedAsset $asset): bool
    {
        return $this->update($user, $asset);
    }

    public function restore(User $user, FixedAsset $asset): bool
    {
        return $this->update($user, $asset);
    }

    public function forceDelete(User $user, FixedAsset $asset): bool
    {
        return false;
    }
}
