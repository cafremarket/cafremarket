<?php

return [
    'host' => env('CHAT_SOCKET_HOST', '0.0.0.0'),
    'port' => (int) env('CHAT_SOCKET_PORT', 6002),
    'client_host' => env('CHAT_SOCKET_CLIENT_HOST', '127.0.0.1'),
    'scheme' => env('CHAT_SOCKET_SCHEME', 'ws'),
    'client_path' => env('CHAT_SOCKET_CLIENT_PATH', ''),
    'debug' => (bool) env('CHAT_SOCKET_DEBUG', false),
];

