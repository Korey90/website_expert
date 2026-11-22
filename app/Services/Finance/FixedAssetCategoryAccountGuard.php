<?php

namespace App\Services\Finance;

use App\Models\Business;
use App\Models\FixedAssetCategory;
use App\Models\LedgerAccount;
use DomainException;

class FixedAssetCategoryAccountGuard
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{asset_account_id: int, accumulated_depreciation_account_id: int, depreciation_expense_account_id: int}
     */
    public function validateAccounts(Business $business, array $data): array
    {
        $requirements = [
            'asset_account_id' => ['asset', 'fixed_asset'],
            'accumulated_depreciation_account_id' => ['asset', 'contra_asset'],
            'depreciation_expense_account_id' => ['expense', 'depreciation'],
        ];
        $validated = [];

        foreach ($requirements as $field => [$type, $subtype]) {
            $id = filter_var($data[$field] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $account = $id ? LedgerAccount::withoutGlobalScopes()->find((int) $id) : null;

            if (! $account
                || $account->business_id !== $business->id
                || ! $account->is_active
                || ! $account->is_postable
                || $account->type !== $type
                || $account->subtype !== $subtype) {
                throw new DomainException("{$field} must reference an active {$type}/{$subtype} account from the same business.");
            }

            $validated[$field] = (int) $account->id;
        }

        if (count(array_unique(array_values($validated))) !== count($validated)) {
            throw new DomainException('A fixed asset category requires three different ledger accounts.');
        }

        return $validated;
    }

    /**
     * @return array{method: string, useful_life_months: int|null, residual_value_percent: string}
     */
    public function validatePolicy(mixed $method, mixed $usefulLifeMonths, mixed $residualValuePercent): array
    {
        $method = trim((string) $method);

        if (! in_array($method, FixedAssetCategory::DEPRECIATION_METHODS, true)) {
            throw new DomainException('Select a supported book depreciation method.');
        }

        $life = $usefulLifeMonths === null || $usefulLifeMonths === ''
            ? null
            : filter_var($usefulLifeMonths, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1200]]);

        if ($method !== FixedAssetCategory::METHOD_NONE && ! $life) {
            throw new DomainException('A depreciated fixed asset requires a useful life between 1 and 1200 months.');
        }

        if ($method === FixedAssetCategory::METHOD_NONE) {
            $life = null;
        }

        $residual = trim((string) ($residualValuePercent ?? '0'));
        if (! preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $residual) || (float) $residual < 0 || (float) $residual > 100) {
            throw new DomainException('Residual value percentage must be between 0 and 100 with at most two decimal places.');
        }

        return [
            'method' => $method,
            'useful_life_months' => $life ? (int) $life : null,
            'residual_value_percent' => number_format((float) $residual, 2, '.', ''),
        ];
    }
}
