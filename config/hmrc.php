<?php

return [
    'environment' => env('HMRC_ENVIRONMENT', 'sandbox'),
    'api_version' => '1.0',
    'timeout' => (int) env('HMRC_TIMEOUT', 20),
    'oauth_state_ttl' => (int) env('HMRC_OAUTH_STATE_TTL', 600),
    'scopes' => ['read:vat', 'write:vat'],

    'sandbox' => [
        'base_url' => env('HMRC_SANDBOX_BASE_URL', 'https://test-api.service.hmrc.gov.uk'),
        'authorize_url' => env('HMRC_SANDBOX_AUTHORIZE_URL', 'https://test-www.tax.service.gov.uk/oauth/authorize'),
        'token_url' => env('HMRC_SANDBOX_TOKEN_URL', 'https://test-api.service.hmrc.gov.uk/oauth/token'),
        'client_id' => env('HMRC_SANDBOX_CLIENT_ID'),
        'client_secret' => env('HMRC_SANDBOX_CLIENT_SECRET'),
        'redirect_uri' => env('HMRC_SANDBOX_REDIRECT_URI', env('APP_URL').'/admin/hmrc/oauth/callback'),
    ],

    'production' => [
        'base_url' => env('HMRC_PRODUCTION_BASE_URL', 'https://api.service.hmrc.gov.uk'),
        'authorize_url' => env('HMRC_PRODUCTION_AUTHORIZE_URL', 'https://www.tax.service.gov.uk/oauth/authorize'),
        'token_url' => env('HMRC_PRODUCTION_TOKEN_URL', 'https://api.service.hmrc.gov.uk/oauth/token'),
        'client_id' => env('HMRC_PRODUCTION_CLIENT_ID'),
        'client_secret' => env('HMRC_PRODUCTION_CLIENT_SECRET'),
        'redirect_uri' => env('HMRC_PRODUCTION_REDIRECT_URI', env('APP_URL').'/admin/hmrc/oauth/callback'),
    ],
];
