<?php

namespace App\Actions\Finance;

use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\User;
use App\Services\Finance\FixedAssetCategoryAccountGuard;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateFixedAssetAction
{
    public function __construct(private readonly FixedAssetCategoryAccountGuard $guard) {}

    /** @param array<string, mixed> $data */
    public function execute(FixedAsset $asset, User $actor, array $data): FixedAsset
    {
        return DB::transaction(function () use ($asset, $actor, $data): FixedAsset {
            $locked = FixedAsset::withoutGlobalScopes()->lockForUpdate()->findOrFail($asset->id);
            $this->assertAuthorized($locked, $actor);
            $categoryId = filter_var($data['fixed_asset_category_id'] ?? $locked->fixed_asset_category_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $category = $categoryId ? FixedAssetCategory::withoutGlobalScopes()->find((int) $categoryId) : null;

            if (! $category || $category->business_id !== $locked->business_id || ! $category->is_active) {
                throw new DomainException('A fixed asset requires an active category from the same business.');
            }

            $name = trim((string) ($data['name'] ?? $locked->name));
            if ($name === '' || mb_strlen($name) > 255) {
                throw new DomainException('A fixed asset requires a name of at most 255 characters.');
            }

            $policy = $this->guard->validatePolicy(
                $data['depreciation_method'] ?? $locked->depreciation_method,
                $data['useful_life_months'] ?? $locked->useful_life_months,
                $data['residual_value_percent'] ?? $locked->residual_value_percent,
            );
            $details = $this->normalizeDetails($data);

            $locked->update([
                ...$details,
                'fixed_asset_category_id' => $category->id,
                'name' => $name,
                'depreciation_method' => $policy['method'],
                'useful_life_months' => $policy['useful_life_months'],
                'residual_value_percent' => $policy['residual_value_percent'],
                'updated_by' => $actor->id,
            ]);

            return $locked->fresh();
        }, 3);
    }

    private function assertAuthorized(FixedAsset $asset, User $actor): void
    {
        if ($asset->status !== FixedAsset::STATUS_DRAFT) {
            throw new DomainException('Only a draft fixed asset can be edited.');
        }

        if (! $actor->businessMemberships()->where('business_id', $asset->business_id)->where('is_active', true)->exists()
            || ! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot manage fixed assets for this business.');
        }
    }

    /** @param array<string, mixed> $data
     * @return array<string, string|null>
     */
    private function normalizeDetails(array $data): array
    {
        $limits = ['description' => 10000, 'serial_number' => 100, 'location' => 150, 'notes' => 10000];
        $details = [];

        foreach ($limits as $field => $limit) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = trim((string) ($data[$field] ?? ''));
            if (mb_strlen($value) > $limit) {
                throw new DomainException("{$field} exceeds its maximum length of {$limit} characters.");
            }

            $details[$field] = $value === '' ? null : $value;
        }

        return $details;
    }
}
