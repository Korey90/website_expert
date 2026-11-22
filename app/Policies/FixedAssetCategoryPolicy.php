<?php

namespace App\Policies;

use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\User;
use App\Support\PermissionHelper;

class FixedAssetCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return PermissionHelper::allows($user, 'view_finance');
    }

    public function view(User $user, FixedAssetCategory $category): bool
    {
        return $this->viewAny($user) && currentBusiness()?->id === $category->business_id;
    }

    public function create(User $user): bool
    {
        return PermissionHelper::allows($user, 'manage_finance');
    }

    public function update(User $user, FixedAssetCategory $category): bool
    {
        return $this->create($user) && currentBusiness()?->id === $category->business_id;
    }

    public function delete(User $user, FixedAssetCategory $category): bool
    {
        return $this->update($user, $category)
            && ! FixedAsset::withoutGlobalScopes()
                ->withTrashed()
                ->where('fixed_asset_category_id', $category->id)
                ->exists();
    }

    public function forceDelete(User $user, FixedAssetCategory $category): bool
    {
        return false;
    }
}
