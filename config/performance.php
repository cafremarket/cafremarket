<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API response cache
    |--------------------------------------------------------------------------
    |
    | Cache JSON for anonymous / catalog API reads in Redis (falls back to the
    | default cache store). Disabled in phpunit so feature tests stay live.
    |
    */

    'api_cache' => env('API_CACHE_ENABLED', true),

    'ttl' => [
        'catalog' => (int) env('API_CACHE_TTL_CATALOG', 300),
        'listing' => (int) env('API_CACHE_TTL_LISTING', 180),
        'item' => (int) env('API_CACHE_TTL_ITEM', 120),
        'shops' => (int) env('API_CACHE_TTL_SHOPS', 180),
        'geo' => (int) env('API_CACHE_TTL_GEO', 120),
        'config' => (int) env('API_CACHE_TTL_CONFIG', 600),
        'vendor_stats' => (int) env('API_CACHE_TTL_VENDOR_STATS', 60),
        'popular' => (int) env('API_CACHE_TTL_POPULAR', 300),
    ],

];
