<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\LedgerAccount;
use App\Services\Finance\BankAccountDetailsProtector;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BankAccount> */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        $business = Business::query()->firstOrCreate(
            ['slug' => 'bank-account-factory-business'],
            ['name' => 'Bank Account Factory Business', 'is_active' => true],
        );
        $ledgerAccount = LedgerAccount::withoutGlobalScopes()->firstOrCreate(
            ['business_id' => $business->id, 'code' => '1199'],
            [
                'name' => ['en' => 'Factory bank'],
                'type' => 'asset',
                'subtype' => 'cash',
                'normal_balance' => 'debit',
                'is_postable' => true,
                'is_system' => false,
                'is_active' => true,
            ],
        );
        $protected = app(BankAccountDetailsProtector::class)->protect(
            fake()->unique()->numerify('########'),
            fake()->numerify('######'),
        );

        return [
            'business_id' => $business->id,
            'ledger_account_id' => $ledgerAccount->id,
            'name' => fake()->words(2, true),
            'bank_name' => fake()->company(),
            ...$protected,
            'currency' => 'GBP',
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ACTIVE,
        ];
    }
}
