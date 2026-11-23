<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FixedAsset> */
class FixedAssetFactory extends Factory
{
    protected $model = FixedAsset::class;

    public function definition(): array
    {
        return [
            'business_id' => fn (): string => Business::factory()->create()->id,
            'fixed_asset_category_id' => fn (array $attributes): int => FixedAssetCategory::factory()->create(['business_id' => $attributes['business_id']])->id,
            'number' => fake()->unique()->bothify('FA-2026-###'),
            'name' => fake()->words(3, true),
            'status' => FixedAsset::STATUS_DRAFT,
            'depreciation_method' => FixedAssetCategory::METHOD_STRAIGHT_LINE,
            'useful_life_months' => 36,
            'residual_value_percent' => 0,
            'depreciation_rate_percent' => null,
        ];
    }
}
