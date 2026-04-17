<?php

return [
    'token' => env('FCM_TOKEN'),
    // Optional absolute path to CA bundle file (cacert.pem).
    // Useful on local Windows/PHP setups to fix cURL SSL trust issues.
    'ca_bundle' => env('CURL_CA_BUNDLE'),
    // Keep SSL verification enabled by default; can disable only for local debugging.
    'verify_ssl' => env('FCM_VERIFY_SSL', true),
];
