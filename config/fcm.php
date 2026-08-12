<?php

return [
    // Driver: auto (prefer HTTP v1 when service account exists), v1, or legacy
    'driver' => env('FCM_DRIVER', 'auto'),

    // Legacy FCM server keys (fallback) — one key per Firebase project
    'token' => env('FCM_TOKEN'),
    'token_customer' => env('FCM_TOKEN_CUSTOMER', env('FCM_TOKEN')),
    'token_vendor' => env('FCM_TOKEN_VENDOR', env('FCM_TOKEN')),
    'token_delivery' => env('FCM_TOKEN_DELIVERY', env('FCM_TOKEN_VENDOR', env('FCM_TOKEN'))),

    // FCM HTTP v1 — one service account per Firebase project
    // Customer app: cafremarketm
    'customer' => [
        'project_id' => env('FCM_CUSTOMER_PROJECT_ID', 'cafremarketm'),
        'credentials' => env('FCM_CUSTOMER_CREDENTIALS', storage_path('app/firebase/customer-service-account.json')),
    ],
    // Vendor app: cafremarket-4f820
    'vendor' => [
        'project_id' => env('FCM_VENDOR_PROJECT_ID', 'cafremarket-4f820'),
        'credentials' => env('FCM_VENDOR_CREDENTIALS', storage_path('app/firebase/vendor-service-account.json')),
    ],
    // Delivery app: cafreentrega (separate project)
    'delivery' => [
        'project_id' => env('FCM_DELIVERY_PROJECT_ID', 'cafreentrega'),
        'credentials' => env('FCM_DELIVERY_CREDENTIALS', storage_path('app/firebase/delivery-service-account.json')),
    ],

    'ca_bundle' => env('CURL_CA_BUNDLE'),
    'verify_ssl' => env('FCM_VERIFY_SSL', true),

    'campaign_chunk' => (int) env('FCM_CAMPAIGN_CHUNK', 80),
];
