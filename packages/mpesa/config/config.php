<?php

return [

    /*
    |--------------------------------------------------------------------------
    | M-Pesa Mozambique (Vodacom) – C2B uses encrypted API key as Bearer
    |--------------------------------------------------------------------------
    */

    'api' => [
        'api_key'                 => env('MPESA_MZ_API_KEY', env('MPESA_API_KEY', env('MPESA_CONSUMER_KEY'))),
        'public_key'              => env('MPESA_MZ_PUBLIC_KEY', env('MPESA_PUBLIC_KEY', env('MPESA_CONSUMER_SECRET'))),
        'sandbox'                  => env('MPESA_MZ_SANDBOX', env('MPESA_SANDBOX', true)),
        'initiator_identifier'     => env('MPESA_MZ_INITIATOR_IDENTIFIER', 'apiuser'),
        'security_credential'      => env('MPESA_MZ_SECURITY_CREDENTIAL', ''),
    ],

    'service_provider_code' => env('MPESA_MZ_SERVICE_PROVIDER_CODE', env('MPESA_SERVICE_PROVIDER_CODE', env('MPESA_SHORT_CODE', env('MPESA_LIPA_NA')))),

    'base_url' => env('MPESA_MZ_BASE_URL', 'https://api.sandbox.vm.co.mz:18352'),

    // Query uses port 18353 in mpesa-mz-nodejs-lib; set if different
    'query_base_url' => env('MPESA_MZ_QUERY_BASE_URL'),

    'path_prefix' => env('MPESA_MZ_PATH_PREFIX', '/ipg/v1x'),

    'country' => env('MPESA_MZ_COUNTRY', env('MPESA_COUNTRY', 'MZ')),

    // Same as mpesa-mz-nodejs-lib: origin required by API/WAF (e.g. developer.mpesa.vm.co.mz)
    'origin' => env('MPESA_MZ_ORIGIN', env('MPESA_MZ_CORS_ORIGIN', env('MPESA_ORIGIN', 'developer.mpesa.vm.co.mz'))),

    // Callback base URL for M-Pesa to notify payment result. Set on server to your public URL (e.g. https://yourdomain.com).
    'callback_base_url' => env('MPESA_CALLBACK_BASE_URL', env('APP_URL', 'http://localhost')),

    // Set to false only if SSL connection to M-Pesa API fails (e.g. certificate chain issue on server). Prefer fixing server CA bundle.
    'ssl_verify' => env('MPESA_MZ_SSL_VERIFY', true),

    // Set to false on production if your server cannot reach M-Pesa Query API (port 18353). Confirmation will then rely only on the callback.
    'query_enabled' => env('MPESA_QUERY_ENABLED', true),
];
