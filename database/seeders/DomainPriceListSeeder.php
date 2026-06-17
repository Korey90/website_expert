<?php

namespace Database\Seeders;

use App\Models\DomainPriceList;
use App\Services\Currency\CurrencyPriceCalculator;
use Illuminate\Database\Seeder;

class DomainPriceListSeeder extends Seeder
{
    /**
     * Retail prices (GBP) based on Openprovider non-member wholesale rates (29/05/2026).
     * Wholesale cost is stored in the notes field for reference.
     * Edit retail prices via the admin panel: Domain Price List.
     *
     * Columns: TLD, Register, Renew, Transfer, notes (wholesale cost)
     */
    public function run(): void
    {
        $calculator = app(CurrencyPriceCalculator::class);
        $currencyRates = [
            'GBP' => 1.00,
            'EUR' => 1.18,
            'PLN' => 4.93,
        ];

        $prices = [
            // TLD          Register  Renew   Transfer  Notes (Openprovider wholesale: create / renew / transfer)
            ['.co.uk',        9.99,   15.99,    0.00,  'OP wholesale GBP: 5.00 / 12.50 / 0.00'],
            ['.uk',           7.99,   12.99,    0.00,  'OP wholesale GBP: 5.00 / 14.99 / 0.00'],
            ['.com',         14.99,   17.99,   12.99,  'OP wholesale GBP: 9.38 / 13.30 / 9.38'],
            ['.net',         16.99,   19.99,   16.99,  'OP wholesale GBP: 12.17 / 15.66 / 15.66'],
            ['.org',         19.99,   19.99,   19.99,  'OP wholesale GBP: 15.66 / 15.66 / 15.66'],
            ['.io',          74.99,   89.99,   74.99,  'OP wholesale GBP: 58.74 / 70.49 / 58.74'],
            ['.co',          34.99,   44.99,   34.99,  'OP wholesale GBP: 25.05 / 35.24 / 25.05'],
            ['.me',          19.99,   29.99,   19.99,  'OP wholesale GBP: 15.46 / 24.56 / 16.37'],
            ['.info',        27.99,   39.99,   27.99,  'OP wholesale GBP: 21.14 / 31.32 / 21.14'],
            ['.biz',         22.99,   27.99,   22.99,  'OP wholesale GBP: 17.22 / 22.70 / 17.22'],
            ['.dev',         24.99,   24.99,   24.99,  'OP wholesale GBP: 18.79 / 18.79 / 18.79'],
            ['.app',         27.99,   27.99,   27.99,  'OP wholesale GBP: 21.14 / 21.14 / 21.14'],
            ['.online',      29.99,   29.99,   29.99,  'OP wholesale GBP: 23.49 / 23.49 / 23.49'],
            ['.store',       49.99,   49.99,   49.99,  'OP wholesale GBP: 41.52 / 41.52 / 39.15'],
            ['.tech',        59.99,   59.99,   59.99,  'OP wholesale GBP: 46.99 / 46.99 / 46.99'],
            ['.ai',         109.99,  129.99,  109.99,  'OP wholesale GBP: 85.39 / 104.97 / 85.39'],
        ];

        foreach ($prices as [$tld, $reg, $renew, $transfer, $notes]) {
            foreach ($currencyRates as $currency => $rate) {
                $roundedRate = $calculator->roundRateUp($rate);
                $isBaseCurrency = $currency === 'GBP';

                DomainPriceList::updateOrCreate(
                    [
                        'tld' => $tld,
                        'currency' => $currency,
                    ],
                    [
                        'register_price' => $isBaseCurrency ? $reg : $calculator->convertFromBase($reg, $rate),
                        'renew_price' => $isBaseCurrency ? $renew : $calculator->convertFromBase($renew, $rate),
                        'transfer_price' => $isBaseCurrency ? $transfer : $calculator->convertFromBase($transfer, $rate),
                        'is_active' => true,
                        'notes' => $isBaseCurrency
                            ? $notes
                            : "{$notes}; seeded from GBP with rounded {$currency} rate {$roundedRate}",
                    ],
                );
            }
        }
    }
}
