<?php

return [
    'secret' => env('JWT_SECRET', env('APP_KEY')),

    'algo' => env('JWT_ALGO', 'HS256'),

    'ttl_minutes' => (int) env('JWT_TTL', 60 * 24 * 30),

    'remember_ttl_minutes' => (int) env('JWT_REMEMBER_TTL', 60 * 24 * 365),

    'cookie_path' => env('JWT_COOKIE_PATH', '/'),

    'cookie_domain' => env('JWT_COOKIE_DOMAIN'),

    'secure' => env('JWT_SECURE_COOKIE'),

    'same_site' => env('JWT_SAME_SITE', 'lax'),

    /*
    |--------------------------------------------------------------------------
    | Guard definitions
    |--------------------------------------------------------------------------
    */
    'guards' => [
        'customer' => [
            'model' => App\Models\Customer::class,
            'cookie' => env('JWT_COOKIE_NAME', 'customer_jwt'),
            'active_column' => 'active',
            'clear_fcm_on_logout' => true,
        ],
        'web' => [
            'model' => App\Models\User::class,
            'cookie' => env('JWT_PANEL_COOKIE_NAME', 'panel_jwt'),
            'active_column' => 'active',
            'clear_fcm_on_logout' => false,
        ],
        'vendor_api' => [
            'model' => App\Models\User::class,
            'cookie' => null,
            'active_column' => 'active',
            'clear_fcm_on_logout' => false,
        ],
        'delivery_boy' => [
            'model' => App\Models\DeliveryBoy::class,
            'cookie' => null,
            'active_column' => 'status',
            'clear_fcm_on_logout' => true,
        ],
        'affiliate' => [
            'model' => Incevio\Package\Affiliate\Models\Affiliate::class,
            'cookie' => env('JWT_AFFILIATE_COOKIE_NAME', 'affiliate_jwt'),
            'active_column' => 'active',
            'clear_fcm_on_logout' => false,
        ],
    ],
];
