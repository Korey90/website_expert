<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Lead source type labels
    |--------------------------------------------------------------------------
    */
    'source_types' => [
        'landing_page'  => 'Landing Page',
        'contact_form'  => 'Contact Form',
        'calculator'    => 'Calculator',
        'api'           => 'API',
        'manual'        => 'Manual',
        'import'        => 'CSV Import',
        'referral'      => 'Referral',
    ],

    /*
    |--------------------------------------------------------------------------
    | GDPR: days to retain raw IP address in lead_sources
    |--------------------------------------------------------------------------
    */
    'pii_retention_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Rate limiting for public-facing lead capture forms
    |--------------------------------------------------------------------------
    */
    'public_form_rate_limit' => [
        'max_attempts' => 3,
        'decay_minutes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limiting for the API endpoint /api/v1/leads
    |--------------------------------------------------------------------------
    */
    'api_rate_limit' => [
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default consent version — change when Privacy Policy is updated
    |--------------------------------------------------------------------------
    */
    'consent_version' => '1.0',

];
