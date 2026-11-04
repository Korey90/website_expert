<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\LedgerAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LedgerAccount> */
class LedgerAccountFactory extends Factory
{
    protected $model = LedgerAccount::class;

    public function definition(): array
    {
        return [
            'business_id' => fn (): string => Business::query()->firstOrCreate(
                ['slug' => 'ledger-account-factory-business'],
                ['name' => 'Ledger Account Factory Business', 'is_active' => true],
            )->id,
            'code' => fake()->unique()->numerify('9###'),
            'name' => ['en' => fake()->words(2, true)],
            'type' => 'expense',
            'subtype' => 'other',
            'normal_balance' => 'debit',
            'is_postable' => true,
            'is_system' => false,
            'is_active' => true,
        ];
    }
}
