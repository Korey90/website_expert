<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\FixedAsset;
use App\Models\FixedAssetTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FixedAssetTransaction> */
class FixedAssetTransactionFactory extends Factory
{
    protected $model = FixedAssetTransaction::class;

    public function definition(): array
    {
        return [
            'business_id' => fn (): string => Business::factory()->create()->id,
            'fixed_asset_id' => fn (array $attributes): int => FixedAsset::factory()->create(['business_id' => $attributes['business_id']])->id,
            'type' => FixedAssetTransaction::TYPE_ACQUISITION,
            'status' => FixedAssetTransaction::STATUS_PREPARED,
            'event_date' => fake()->date(),
            'amount_gbp' => fake()->randomFloat(2, 10, 5000),
        ];
    }
}
