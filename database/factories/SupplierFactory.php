<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Supplier> */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'business_id' => fn (): string => Business::query()->firstOrCreate(
                ['slug' => 'supplier-factory-business'],
                ['name' => 'Supplier Factory Business', 'is_active' => true],
            )->id,
            'name' => fake()->company(),
            'trading_name' => fake()->optional()->company(),
            'company_number' => fake()->optional()->numerify('########'),
            'vat_number' => fake()->optional()->numerify('GB#########'),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'address_line1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postcode' => fake()->postcode(),
            'country' => 'GB',
            'default_currency' => 'GBP',
            'payment_terms_days' => 30,
            'status' => Supplier::STATUS_ACTIVE,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => Supplier::STATUS_ARCHIVED]);
    }
}
