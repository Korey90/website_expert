<?php

namespace Database\Factories;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Models\Business;
use App\Models\LedgerAccount;
use App\Models\TaxCode;
use App\Models\User;
use App\Models\VatAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VatAdjustment> */
class VatAdjustmentFactory extends Factory
{
    protected $model = VatAdjustment::class;

    public function definition(): array
    {
        $business = Business::query()->firstOrCreate(
            ['slug' => 'vat-adjustment-factory-business'],
            ['name' => 'VAT Adjustment Factory Business', 'is_active' => true],
        );

        if (! LedgerAccount::withoutGlobalScopes()->where('business_id', $business->id)->exists()) {
            app(InitializeAccountingSetupAction::class)->execute($business);
        }

        return [
            'business_id' => $business->id,
            'number' => 'VADJ-'.now()->format('Y').'-'.$this->faker->unique()->numerify('###'),
            'status' => VatAdjustment::STATUS_DRAFT,
            'adjustment_type' => VatAdjustment::TYPE_ACCOUNTING_ADJUSTMENT,
            'vat_side' => VatAdjustment::SIDE_OUTPUT,
            'direction' => VatAdjustment::DIRECTION_INCREASE,
            'correction_method' => VatAdjustment::METHOD_NOT_ERROR,
            'tax_code_id' => TaxCode::withoutGlobalScopes()->where('business_id', $business->id)->where('code', 'S20')->value('id'),
            'control_ledger_account_id' => LedgerAccount::withoutGlobalScopes()->where('business_id', $business->id)->where('subtype', 'tax_payable')->value('id'),
            'contra_ledger_account_id' => LedgerAccount::withoutGlobalScopes()->where('business_id', $business->id)->where('code', '6900')->value('id'),
            'effective_date' => now()->toDateString(),
            'amount_gbp' => '10.00',
            'is_deliberate' => false,
            'reason' => 'Documented VAT accounting adjustment for testing.',
            'created_by' => User::factory(),
        ];
    }
}
