<?php

return [
    /*
    |--------------------------------------------------------------------------
    | KIRI Engine API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your KIRI Engine API settings.
    |
    */

    'api_key' => env('KIRIENGINE_API_KEY'),
    
    'base_url' => env('KIRIENGINE_BASE_URL', 'https://api.kiriengine.app/api/v1/open/'),
    
    'debug' => env('KIRIENGINE_DEBUG', false),
    
    'verify' => env('KIRIENGINE_VERIFY', true),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook settings for receiving model updates.
    |
    */

    'webhook' => [
        'path' => env('KIRIENGINE_WEBHOOK_PATH', 'kiri-engine-webhook'),
        'storage_path' => env('KIRIENGINE_STORAGE_PATH', 'storage/app/private/kiri-engine'),
        'secret' => env('KIRIENGINE_WEBHOOK_SECRET'),
    ],
];
