<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Explicit origins only — never use "*" with credentials for this API.
    | Configure via FRONTEND_URLS (comma-separated) or FRONTEND_URL.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env(
            'FRONTEND_URLS',
            env('FRONTEND_URL', 'http://localhost:8080')
        ))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-XSRF-TOKEN',
        'X-CSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
