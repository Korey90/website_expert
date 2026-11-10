<?php

return [
    'environment' => env('HMRC_ENVIRONMENT', 'sandbox'),
    'api_version' => '1.0',
    'timeout' => (int) env('HMRC_TIMEOUT', 20),
    'oauth_state_ttl' => (int) env('HMRC_OAUTH_STATE_TTL', 600),
    'scopes' => ['read:vat', 'write:vat'],

    'fraud_prevention' => [
        'browser_context_cookie' => 'hmrc_fph_context',
        'browser_context_ttl' => (int) env('HMRC_FPH_BROWSER_CONTEXT_TTL', 1800),
        'client_public_ip_header' => env('HMRC_CLIENT_PUBLIC_IP_HEADER'),
        'client_public_port_header' => env('HMRC_CLIENT_PUBLIC_PORT_HEADER'),
        'product_name' => env('HMRC_PRODUCT_NAME', 'Website Expert NI'),
        'product_id' => env('HMRC_PRODUCT_ID', 'website-expert-ni'),
        'product_version' => env('HMRC_PRODUCT_VERSION'),
        'vendor_public_ip' => env('HMRC_VENDOR_PUBLIC_IP'),
        'vendor_forwarding_chain' => env('HMRC_VENDOR_FORWARDING_CHAIN'),
        'vendor_license_id' => env('HMRC_VENDOR_LICENSE_ID'),
        'allowed_missing_headers' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('HMRC_FPH_ALLOWED_MISSING_HEADERS', '')),
        ))),
    ],

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
