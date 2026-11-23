<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\User;
use App\Services\Finance\FixedAssetCategoryAccountGuard;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateFixedAssetAction
{
    public function __construct(
        private readonly GenerateDocumentNumberAction $numbers,
        private readonly FixedAssetCategoryAccountGuard $guard,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(Business $business, User $actor, array $data): FixedAsset
    {
        $this->assertAuthorized($business->id, $actor);

        return DB::transaction(function () use ($business, $actor, $data): FixedAsset {
            Business::query()->lockForUpdate()->findOrFail($business->id);
            $category = $this->category($business->id, $data['fixed_asset_category_id'] ?? null);
            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '' || mb_strlen($name) > 255) {
                throw new DomainException('A fixed asset requires a name of at most 255 characters.');
            }

            $policy = $this->guard->validatePolicy(
                $data['depreciation_method'] ?? $category->default_depreciation_method,
                $data['useful_life_months'] ?? $category->default_useful_life_months,
                $data['residual_value_percent'] ?? $category->default_residual_value_percent,
                $data['depreciation_rate_percent'] ?? $category->default_depreciation_rate_percent,
            );
            $details = $this->normalizeDetails($data);

            return FixedAsset::withoutGlobalScopes()->create([
                ...$details,
                'business_id' => $business->id,
                'fixed_asset_category_id' => $category->id,
                'number' => $this->numbers->execute($business->id, 'fixed_asset'),
                'name' => $name,
                'status' => FixedAsset::STATUS_DRAFT,
                'depreciation_method' => $policy['method'],
                'useful_life_months' => $policy['useful_life_months'],
                'residual_value_percent' => $policy['residual_value_percent'],
                'depreciation_rate_percent' => $policy['rate_percent'],
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        }, 3);
    }

    private function category(string $businessId, mixed $id): FixedAssetCategory
    {
        $categoryId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $category = $categoryId ? FixedAssetCategory::withoutGlobalScopes()->find((int) $categoryId) : null;

        if (! $category || $category->business_id !== $businessId || ! $category->is_active) {
            throw new DomainException('A fixed asset requires an active category from the same business.');
        }

        return $category;
    }

    private function assertAuthorized(string $businessId, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()
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
