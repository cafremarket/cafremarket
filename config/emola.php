<?php

return [
    'wsdl' => env('EMOLA_WSDL', 'http://10.229.16.29:8520/BCCSGateway/BCCSGateway?wsdl'),
    'endpoint' => env('EMOLA_ENDPOINT', 'http://10.229.16.29:8520/BCCSGateway'),
    'username' => env('EMOLA_USERNAME'),
    'password' => env('EMOLA_PASSWORD'),
    'partner_code' => env('EMOLA_PARTNER_CODE'),
    'key' => env('EMOLA_KEY'),
    'language' => env('EMOLA_LANGUAGE', 'pt'),
    'callback_url' => env('EMOLA_CALLBACK_URL', 'https://cafremarket.co.mz/api/emola/callback'),
    'timeout_seconds' => (int) env('EMOLA_TIMEOUT_SECONDS', 60),
    'fake' => (bool) env('EMOLA_FAKE', false),
];
