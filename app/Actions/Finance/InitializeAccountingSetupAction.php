<?php

namespace App\Actions\Finance;

use App\Models\AccountingProfile;
use App\Models\Business;
use App\Models\LedgerAccount;
use App\Models\TaxCode;
use App\Models\User;
use App\Support\Finance\DefaultUkChartAccounts;
use App\Support\Finance\DefaultUkTaxCodes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InitializeAccountingSetupAction
{
    /**
     * @return array{profile: AccountingProfile, tax_codes: Collection<int, TaxCode>, ledger_accounts: Collection<int, LedgerAccount>}
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

            $ledgerAccounts = new Collection;
            $accountsByCode = [];

            foreach (DefaultUkChartAccounts::all() as $attributes) {
                $parentCode = $attributes['parent_code'];
                unset($attributes['parent_code']);

                $account = LedgerAccount::withoutGlobalScopes()->firstOrCreate(
                    [
                        'business_id' => $business->id,
                        'code' => $attributes['code'],
                    ],
                    [
                        ...$attributes,
                        'parent_id' => $parentCode ? $accountsByCode[$parentCode]->id : null,
                        'is_system' => true,
                    ],
                );

                $accountsByCode[$account->code] = $account;
                $ledgerAccounts->push($account);
            }

            return [
                'profile' => $profile,
                'tax_codes' => $taxCodes,
                'ledger_accounts' => $ledgerAccounts,
            ];
        });
    }
}
