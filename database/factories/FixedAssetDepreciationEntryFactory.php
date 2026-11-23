<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\FixedAssetDepreciationEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FixedAssetDepreciationEntry> */
class FixedAssetDepreciationEntryFactory extends Factory
{
    protected $model = FixedAssetDepreciationEntry::class;

    public function definition(): array
    {
        return [
            'business_id' => fn (): string => Business::factory()->create()->id,
            'fixed_asset_id' => fn (array $attributes): int => FixedAsset::factory()->create(['business_id' => $attributes['business_id']])->id,
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'posting_date' => '2026-01-31',
            'status' => FixedAssetDepreciationEntry::STATUS_PLANNED,
            'method_snapshot' => FixedAssetCategory::METHOD_STRAIGHT_LINE,
            'useful_life_months_snapshot' => 36,
            'residual_value_gbp' => '0.00',
            'opening_cost_gbp' => '1200.00',
            'opening_accumulated_depreciation_gbp' => '0.00',
            'opening_net_book_value_gbp' => '1200.00',
            'amount_gbp' => '33.33',
            'closing_accumulated_depreciation_gbp' => '33.33',
            'closing_net_book_value_gbp' => '1166.67',
        ];
    }
}
