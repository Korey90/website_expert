<?php

namespace App\Actions\Finance;

use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\FixedAssetDepreciationEntry;
use App\Models\User;
use App\Services\Finance\FixedAssetCategoryAccountGuard;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class SetFixedAssetDepreciationRateAction
{
    public function __construct(private readonly FixedAssetCategoryAccountGuard $guard) {}

    public function execute(FixedAsset $asset, User $actor, mixed $ratePercent): FixedAsset
    {
        return DB::transaction(function () use ($asset, $actor, $ratePercent): FixedAsset {
            $locked = FixedAsset::withoutGlobalScopes()->lockForUpdate()->findOrFail($asset->id);

            if (! $actor->businessMemberships()->where('business_id', $locked->business_id)->where('is_active', true)->exists()
                || ! PermissionHelper::allows($actor, 'manage_finance')) {
                throw new DomainException('The actor cannot complete this fixed asset depreciation policy.');
            }

            if ($locked->status !== FixedAsset::STATUS_ACTIVE
                || $locked->depreciation_method !== FixedAssetCategory::METHOD_REDUCING_BALANCE) {
                throw new DomainException('Only an active reducing-balance asset can use this policy completion action.');
            }

            if ($locked->depreciation_rate_percent !== null) {
                throw new DomainException('The active fixed asset already has an immutable depreciation rate.');
            }

            if (FixedAssetDepreciationEntry::withoutGlobalScopes()
                ->where('fixed_asset_id', $locked->id)
                ->where('status', FixedAssetDepreciationEntry::STATUS_POSTED)
                ->exists()) {
                throw new DomainException('A depreciation rate cannot be completed after depreciation has been posted.');
            }

            $policy = $this->guard->validatePolicy(
                $locked->depreciation_method,
                $locked->useful_life_months,
                $locked->residual_value_percent,
                $ratePercent,
            );
            DB::table('fixed_assets')->where('id', $locked->id)->update([
                'depreciation_rate_percent' => $policy['rate_percent'],
                'updated_by' => $actor->id,
                'updated_at' => now(),
            ]);

            return $locked->fresh();
        }, 3);
    }
}
