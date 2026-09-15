<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Rank Checker
    |--------------------------------------------------------------------------
    |
    | Public "Google Rank Checker" tool. Provider is abstracted behind
    | SerpProviderInterface (see App\Services\Seo) — 'manual' is a free,
    | deterministic stub used until a paid SERP provider is configured.
    |
    */
    'rank_checker' => [

        // manual | dataforseo
        'provider' => env('SERP_PROVIDER', 'manual'),

        // How long an identical (keyword+domain+location+device+language) check is cached.
        'cache_ttl_hours' => (int) env('RANK_CHECK_CACHE_TTL_HOURS', 8),

        // Depth of organic results searched for the target domain.
        'top_results' => (int) env('RANK_CHECK_TOP_RESULTS', 100),

        'default_location' => env('RANK_CHECK_DEFAULT_LOCATION', 'United Kingdom'),
        'default_device' => 'desktop',
        'default_language' => 'en',

        // Predefined locations shown in the public form select.
        // 'location_code' = verified DataForSEO numeric location id (source: /v3/serp/google/locations,
        // country_code=GB, checked 2026-09-07). Sending location_code avoids DataForSEO's strict
        // "location_name must match the locations database exactly" validation.
        'locations' => [
            ['name' => 'United Kingdom', 'location_code' => 2826],
            ['name' => 'Northern Ireland', 'location_code' => 20341],
            ['name' => 'Belfast, United Kingdom', 'location_code' => 1007274],
            ['name' => 'Derry/Londonderry, United Kingdom', 'location_code' => 1007284],
            ['name' => 'Lisburn, United Kingdom', 'location_code' => 1007283],
            ['name' => 'Newry, United Kingdom', 'location_code' => 1007287],
            ['name' => 'Armagh, United Kingdom', 'location_code' => 9231805],
            ['name' => 'Craigavon, United Kingdom', 'location_code' => 9193160],
            ['name' => 'Dungannon, United Kingdom', 'location_code' => 1007279],
        ],
    ],

];
