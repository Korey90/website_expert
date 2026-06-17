<?php

return [
    'default' => env('APP_CURRENCY', 'GBP'),

    'locale_map' => [
        'en' => 'GBP',
        'pl' => 'PLN',
        'pt' => 'EUR',
    ],

    'country_map' => [
        // British Pound
        'GB' => 'GBP',
        'GI' => 'GBP', // Gibraltar

        // Polish Zloty
        'PL' => 'PLN',

        // Euro — Eurozone
        'AT' => 'EUR', 'BE' => 'EUR', 'CY' => 'EUR', 'EE' => 'EUR',
        'FI' => 'EUR', 'FR' => 'EUR', 'DE' => 'EUR', 'GR' => 'EUR',
        'IE' => 'EUR', 'IT' => 'EUR', 'LV' => 'EUR', 'LT' => 'EUR',
        'LU' => 'EUR', 'MT' => 'EUR', 'NL' => 'EUR', 'PT' => 'EUR',
        'SK' => 'EUR', 'SI' => 'EUR', 'ES' => 'EUR',

        // Euro — EU non-Eurozone (local currencies not supported, EUR is nearest)
        'BG' => 'EUR', 'CZ' => 'EUR', 'DK' => 'EUR', 'HR' => 'EUR',
        'HU' => 'EUR', 'RO' => 'EUR', 'SE' => 'EUR',

        // Euro — Other European countries
        'AL' => 'EUR', 'AD' => 'EUR', 'AM' => 'EUR', 'AZ' => 'EUR',
        'BY' => 'EUR', 'BA' => 'EUR', 'GE' => 'EUR', 'IS' => 'EUR',
        'LI' => 'EUR', 'MD' => 'EUR', 'ME' => 'EUR', 'MK' => 'EUR',
        'MC' => 'EUR', 'NO' => 'EUR', 'RS' => 'EUR', 'SM' => 'EUR',
        'CH' => 'EUR', 'TR' => 'EUR', 'UA' => 'EUR', 'VA' => 'EUR',
        'XK' => 'EUR',
    ],

    'locale_country_map' => [
        'en' => 'GB',
        'pl' => 'PL',
        'pt' => 'PT',
    ],

    'supported' => [
        'GBP' => [
            'code' => 'GBP',
            'name' => 'British Pound',
            'symbol' => '£',
            'display_locale' => 'en-GB',
            'decimal_digits' => 2,
            'minor_unit' => 100,
            'symbol_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ],
        'EUR' => [
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => '€',
            'display_locale' => 'pt-PT',
            'decimal_digits' => 2,
            'minor_unit' => 100,
            'symbol_position' => 'after',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
        ],
        'PLN' => [
            'code' => 'PLN',
            'name' => 'Polish Zloty',
            'symbol' => 'zł',
            'display_locale' => 'pl-PL',
            'decimal_digits' => 2,
            'minor_unit' => 100,
            'symbol_position' => 'after',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
        ],
    ],

    'rate_rounding' => [
        'step' => 0.10,
    ],

    // ISO 3166-1 alpha-2 country code to simulate for local development.
    // Set GEOIP_TEST_COUNTRY=PL in .env to bypass IP detection.
    // Always takes priority over CF-IPCountry and ip-api.com.
    // Leave empty (or remove) in production.
    'test_country' => env('GEOIP_TEST_COUNTRY'),

    'payu' => [
        'supported_currencies' => ['GBP', 'EUR', 'PLN'],
        'bank_transfer_currencies' => ['CZK', 'EUR', 'PLN'],
    ],
];
