<?php

namespace Database\Factories;

use App\Models\AccountingPeriod;
use App\Models\Business;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AccountingPeriod> */
class AccountingPeriodFactory extends Factory
{
    protected $model = AccountingPeriod::class;

    public function definition(): array
    {
        return [
            'business_id' => fn (): string => Business::query()->firstOrCreate(
                ['slug' => 'accounting-period-factory-business'],
                ['name' => 'Accounting Period Factory Business', 'is_active' => true],
            )->id,
            'name' => 'Financial year '.now()->year,
            'financial_year' => now()->year,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'status' => AccountingPeriod::STATUS_OPEN,
            'transition_history' => [],
        ];
    }
}
