<?php

namespace App\Support\Finance;

final class DefaultUkTaxCodes
{
    /**
     * These are application defaults, not a substitute for an accountant's
     * assessment of the correct VAT treatment for a transaction.
     *
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        $standardMapping = [
            'output_vat' => [1],
            'input_vat' => [4],
            'net_sales' => [6],
            'net_purchases' => [7],
        ];

        $netMapping = [
            'net_sales' => [6],
            'net_purchases' => [7],
        ];

        return [
            [
                'code' => 'S20',
                'label' => ['en' => 'Standard rate 20%', 'pl' => 'Stawka standardowa 20%', 'pt' => 'Taxa normal 20%'],
                'type' => 'standard',
                'rate' => 20,
                'input_eligible' => true,
                'output_eligible' => true,
                'vat_return_mapping' => $standardMapping,
                'valid_from' => '2011-01-04',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'code' => 'R5',
                'label' => ['en' => 'Reduced rate 5%', 'pl' => 'Stawka obniżona 5%', 'pt' => 'Taxa reduzida 5%'],
                'type' => 'reduced',
                'rate' => 5,
                'input_eligible' => true,
                'output_eligible' => true,
                'vat_return_mapping' => $standardMapping,
                'valid_from' => '1997-09-01',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'code' => 'Z0',
                'label' => ['en' => 'Zero rate', 'pl' => 'Stawka zerowa', 'pt' => 'Taxa zero'],
                'type' => 'zero',
                'rate' => 0,
                'input_eligible' => true,
                'output_eligible' => true,
                'vat_return_mapping' => $netMapping,
                'valid_from' => '1973-04-01',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'code' => 'EXEMPT',
                'label' => ['en' => 'VAT exempt', 'pl' => 'Zwolnione z VAT', 'pt' => 'Isento de IVA'],
                'type' => 'exempt',
                'rate' => 0,
                'input_eligible' => false,
                'output_eligible' => false,
                'vat_return_mapping' => $netMapping,
                'valid_from' => '1973-04-01',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'code' => 'OUTSIDE',
                'label' => ['en' => 'Outside the scope of VAT', 'pl' => 'Poza zakresem VAT', 'pt' => 'Fora do âmbito do IVA'],
                'type' => 'outside_scope',
                'rate' => 0,
                'input_eligible' => false,
                'output_eligible' => false,
                'vat_return_mapping' => [],
                'valid_from' => '1973-04-01',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'code' => 'RC20',
                'label' => ['en' => 'Reverse charge 20%', 'pl' => 'Odwrotne obciążenie 20%', 'pt' => 'Autoliquidação 20%'],
                'type' => 'reverse_charge',
                'rate' => 20,
                'input_eligible' => true,
                'output_eligible' => true,
                'vat_return_mapping' => $standardMapping,
                'valid_from' => '1973-04-01',
                'is_active' => true,
                'is_system' => true,
            ],
        ];
    }
}
