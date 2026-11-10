<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use App\Models\VatReturn;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VatReturn> */
class VatReturnFactory extends Factory
{
    protected $model = VatReturn::class;

    public function definition(): array
    {
        $snapshot = [
            'calculation' => ['boxes' => []],
            'schema_version' => 1,
            'sources' => ['rows' => []],
        ];

        return [
            'business_id' => Business::factory(),
            'number' => 'VATR-'.now()->format('Y').'-'.$this->faker->unique()->numerify('###'),
            'status' => VatReturn::STATUS_DRAFT,
            'period_from' => '2026-01-01',
            'period_to' => '2026-03-31',
            'snapshot_version' => 1,
            'snapshot' => $snapshot,
            'snapshot_hash' => hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
            'source_journal_count' => 0,
            'source_line_count' => 0,
            'warning_count' => 0,
            'created_by' => User::factory(),
        ];
    }
}
