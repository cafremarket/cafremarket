<?php

return [
    'enabled' => env('HYPERLOCAL_ENABLED', true),
    'require_location_for_browse' => env('HYPERLOCAL_REQUIRE_LOCATION', true),
    'ignore_shipping_zones' => env('HYPERLOCAL_IGNORE_SHIPPING_ZONES', true),
    'default_buyer_search_radius_km' => (float) env('HYPERLOCAL_DEFAULT_RADIUS_KM', 10),
    'max_delivery_assignment_radius_km' => (float) env('HYPERLOCAL_MAX_DISPATCH_RADIUS_KM', 15),
    'default_shop_service_radius_km' => (float) env('HYPERLOCAL_DEFAULT_SHOP_RADIUS_KM', 5),
    'rider_accept_timeout_min' => (int) env('HYPERLOCAL_RIDER_ACCEPT_TIMEOUT_MIN', 5),
    'google_maps_api_key' => env('GOOGLE_PLACE_KEY'),
    'require_store_location_for_verification' => env('HYPERLOCAL_REQUIRE_STORE_LOCATION', true),
];
