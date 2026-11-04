<?php

namespace App\Actions\Finance;

use App\Models\AccountingProfile;
use App\Models\Business;
use App\Models\TaxCode;
use App\Models\User;
use App\Support\Finance\DefaultUkTaxCodes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InitializeAccountingSetupAction
{
    /**
     * @return array{profile: AccountingProfile, tax_codes: Collection<int, TaxCode>}
     */
    public function execute(Business $business, ?User $actor = null): array
    {
        return DB::transaction(function () use ($business, $actor): array {
            $profile = AccountingProfile::withoutGlobalScopes()->firstOrCreate(
                ['business_id' => $business->id],
                [
                    'legal_name' => $business->name,
                    'functional_currency' => 'GBP',
                    'accounting_basis' => 'accrual',
                    'created_by' => $actor?->id,
                ],
            );

            $taxCodes = new Collection;

            foreach (DefaultUkTaxCodes::all() as $attributes) {
                $taxCode = TaxCode::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->where('code', $attributes['code'])
                    ->whereDate('valid_from', $attributes['valid_from'])
                    ->first();

                $taxCodes->push($taxCode ?? TaxCode::withoutGlobalScopes()->create([
                    'business_id' => $business->id,
                    ...$attributes,
                ]));
            }

            return [
                'profile' => $profile,
                'tax_codes' => $taxCodes,
            ];
        });
    }
}
