<?php

return [
    'api_key' => env('KIRIENGINE_API_KEY'),
    'base_url' => env('KIRIENGINE_BASE_URL', 'https://api.kiriengine.app/api/'),
    'debug' => env('KIRIENGINE_DEBUG', false),
    'verify' => env('KIRIENGINE_VERIFY', true),
];
