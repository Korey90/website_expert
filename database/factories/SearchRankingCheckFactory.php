<?php

namespace Database\Factories;

use App\Models\SearchRankingCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchRankingCheck>
 */
class SearchRankingCheckFactory extends Factory
{
    protected $model = SearchRankingCheck::class;

    public function definition(): array
    {
        return [
            'business_id' => null,
            'keyword' => $this->faker->words(3, true),
            'domain' => $this->faker->domainName(),
            'location' => 'United Kingdom',
            'location_code' => null,
            'device' => 'desktop',
            'language' => 'en',
            'status' => SearchRankingCheck::STATUS_FOUND,
            'position' => $this->faker->numberBetween(1, 100),
            'ranking_url' => 'https://'.$this->faker->domainName().'/',
            'provider' => 'manual',
            'from_cache' => false,
            'ip_hash' => hash('sha256', $this->faker->ipv4()),
            'error_message' => null,
            'created_at' => now(),
        ];
    }

    public function notFound(): static
    {
        return $this->state([
            'status' => SearchRankingCheck::STATUS_NOT_FOUND,
            'position' => null,
            'ranking_url' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => SearchRankingCheck::STATUS_FAILED,
            'position' => null,
            'ranking_url' => null,
            'error_message' => 'SERP provider timeout',
        ]);
    }
}
