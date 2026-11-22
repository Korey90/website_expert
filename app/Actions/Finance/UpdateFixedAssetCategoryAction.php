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

class UpdateFixedAssetCategoryAction
{
    public function __construct(private readonly FixedAssetCategoryAccountGuard $guard) {}

    /** @param array<string, mixed> $data */
    public function execute(FixedAssetCategory $category, User $actor, array $data): FixedAssetCategory
    {
        return DB::transaction(function () use ($category, $actor, $data): FixedAssetCategory {
            $locked = FixedAssetCategory::withoutGlobalScopes()->lockForUpdate()->findOrFail($category->id);
            $this->assertAuthorized($locked->business_id, $actor);
            $business = Business::query()->lockForUpdate()->findOrFail($locked->business_id);
            $code = strtoupper(trim((string) ($data['code'] ?? $locked->code)));
            $names = is_array($data['name'] ?? null) ? $data['name'] : $locked->getTranslations('name');
            $en = trim((string) ($names['en'] ?? ''));
            $pl = trim((string) ($names['pl'] ?? ''));
            $pt = trim((string) ($names['pt'] ?? ''));
            $accounts = $this->guard->validateAccounts($business, [
                'asset_account_id' => $data['asset_account_id'] ?? $locked->asset_account_id,
                'accumulated_depreciation_account_id' => $data['accumulated_depreciation_account_id'] ?? $locked->accumulated_depreciation_account_id,
                'depreciation_expense_account_id' => $data['depreciation_expense_account_id'] ?? $locked->depreciation_expense_account_id,
            ]);
            $policy = $this->guard->validatePolicy(
                $data['default_depreciation_method'] ?? $locked->default_depreciation_method,
                $data['default_useful_life_months'] ?? $locked->default_useful_life_months,
                $data['default_residual_value_percent'] ?? $locked->default_residual_value_percent,
            );

            if ($code === '' || strlen($code) > 32 || $en === '' || mb_strlen($en) > 255 || mb_strlen($pl) > 255 || mb_strlen($pt) > 255) {
                throw new DomainException('A fixed asset category requires a valid code and English name.');
            }

            if (FixedAssetCategory::withoutGlobalScopes()->where('business_id', $locked->business_id)->where('code', $code)->whereKeyNot($locked->id)->exists()) {
                throw new DomainException('The fixed asset category code is already in use for this business.');
            }

            $accountingFieldsChanged = collect($accounts)->contains(fn (int $id, string $field): bool => (int) $locked->{$field} !== $id)
                || $locked->default_depreciation_method !== $policy['method']
                || $locked->default_useful_life_months !== $policy['useful_life_months']
                || (string) $locked->default_residual_value_percent !== $policy['residual_value_percent'];

            if ($accountingFieldsChanged && FixedAsset::withoutGlobalScopes()
                ->where('fixed_asset_category_id', $locked->id)
                ->where('status', '!=', FixedAsset::STATUS_DRAFT)
                ->exists()) {
                throw new DomainException('Accounting defaults cannot change after the category has non-draft assets.');
            }

            $locked->update([
                'code' => $code,
                'name' => ['en' => $en, 'pl' => $pl ?: $en, 'pt' => $pt ?: $en],
                ...$accounts,
                'default_depreciation_method' => $policy['method'],
                'default_useful_life_months' => $policy['useful_life_months'],
                'default_residual_value_percent' => $policy['residual_value_percent'],
                'is_active' => (bool) ($data['is_active'] ?? $locked->is_active),
            ]);

            return $locked->fresh();
        }, 3);
    }

    private function assertAuthorized(string $businessId, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()
            || ! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot manage fixed asset categories for this business.');
        }
    }
}
