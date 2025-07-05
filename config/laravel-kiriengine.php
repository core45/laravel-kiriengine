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

    /*
    |--------------------------------------------------------------------------
    | API Key Resolver
    |--------------------------------------------------------------------------
    |
    | For multi-tenant applications, you can provide a custom resolver function
    | that returns the API key dynamically (e.g., from the authenticated user).
    | 
    | Example for user-specific API keys:
    | 'api_key_resolver' => function() {
    |     return auth()->user()->kiri_api_key ?? null;
    | }
    |
    | If the resolver returns null or empty, it will fall back to the api_key above.
    |
    */
    'api_key_resolver' => null,

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
        'secret' => env('KIRIENGINE_WEBHOOK_SECRET'),
    ],
];
