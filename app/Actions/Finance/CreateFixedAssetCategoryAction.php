<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\FixedAssetCategory;
use App\Models\User;
use App\Services\Finance\FixedAssetCategoryAccountGuard;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateFixedAssetCategoryAction
{
    public function __construct(private readonly FixedAssetCategoryAccountGuard $guard) {}

    /** @param array<string, mixed> $data */
    public function execute(Business $business, User $actor, array $data): FixedAssetCategory
    {
        $this->assertAuthorized($business->id, $actor);
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $name = $this->normalizeName($data['name'] ?? null);
        $accounts = $this->guard->validateAccounts($business, $data);
        $policy = $this->guard->validatePolicy(
            $data['default_depreciation_method'] ?? null,
            $data['default_useful_life_months'] ?? null,
            $data['default_residual_value_percent'] ?? 0,
            $data['default_depreciation_rate_percent'] ?? null,
        );

        if ($code === '' || strlen($code) > 32) {
            throw new DomainException('A fixed asset category requires a code of at most 32 characters.');
        }

        return DB::transaction(function () use ($business, $code, $name, $accounts, $policy, $data): FixedAssetCategory {
            Business::query()->lockForUpdate()->findOrFail($business->id);

            if (FixedAssetCategory::withoutGlobalScopes()->where('business_id', $business->id)->where('code', $code)->exists()) {
                throw new DomainException('The fixed asset category code is already in use for this business.');
            }

            return FixedAssetCategory::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'code' => $code,
                'name' => $name,
                ...$accounts,
                'default_depreciation_method' => $policy['method'],
                'default_useful_life_months' => $policy['useful_life_months'],
                'default_residual_value_percent' => $policy['residual_value_percent'],
                'default_depreciation_rate_percent' => $policy['rate_percent'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);
        }, 3);
    }

    private function assertAuthorized(string $businessId, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()
            || ! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot manage fixed asset categories for this business.');
        }
    }

    /** @return array{en: string, pl: string, pt: string} */
    private function normalizeName(mixed $value): array
    {
        $names = is_array($value) ? $value : [];
        $en = trim((string) ($names['en'] ?? ''));
        $pl = trim((string) ($names['pl'] ?? ''));
        $pt = trim((string) ($names['pt'] ?? ''));

        if ($en === '' || mb_strlen($en) > 255 || mb_strlen($pl) > 255 || mb_strlen($pt) > 255) {
            throw new DomainException('A fixed asset category requires a valid English name.');
        }

        return ['en' => $en, 'pl' => $pl ?: $en, 'pt' => $pt ?: $en];
    }
}
