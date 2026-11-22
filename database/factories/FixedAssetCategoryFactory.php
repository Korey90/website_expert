<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\FixedAssetCategory;
use App\Models\LedgerAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FixedAssetCategory> */
class FixedAssetCategoryFactory extends Factory
{
    protected $model = FixedAssetCategory::class;

    public function definition(): array
    {
        return [
            'business_id' => fn (): string => Business::factory()->create()->id,
            'code' => fake()->unique()->bothify('FA-###'),
            'name' => ['en' => fake()->words(2, true)],
            'asset_account_id' => fn (array $attributes): int => $this->account($attributes['business_id'], 'fixed_asset', 'asset', '9150'),
            'accumulated_depreciation_account_id' => fn (array $attributes): int => $this->account($attributes['business_id'], 'contra_asset', 'asset', '9160'),
            'depreciation_expense_account_id' => fn (array $attributes): int => $this->account($attributes['business_id'], 'depreciation', 'expense', '9700'),
            'default_depreciation_method' => FixedAssetCategory::METHOD_STRAIGHT_LINE,
            'default_useful_life_months' => 36,
            'default_residual_value_percent' => 0,
            'is_active' => true,
        ];
    }

    private function account(string $businessId, string $subtype, string $type, string $code): int
    {
        return LedgerAccount::withoutGlobalScopes()->firstOrCreate(
            ['business_id' => $businessId, 'code' => $code],
            [
                'name' => ['en' => "Factory {$subtype}"],
                'type' => $type,
                'subtype' => $subtype,
                'normal_balance' => $subtype === 'contra_asset' ? 'credit' : 'debit',
                'is_postable' => true,
                'is_system' => false,
                'is_active' => true,
            ],
        )->id;
    }
}
