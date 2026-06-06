<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Mengizinkan web frontend AgriCloud (port 8006) memanggil API.
    | Mobile (Flutter) native tidak butuh CORS.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_unique(array_merge(
        [
            env('APP_FRONTEND_URL'),
            'http://localhost:8006',
            'http://172.24.170.241:8006',
        ],
        // CORS_ALLOWED_ORIGINS = daftar origin produksi, dipisah koma.
        array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))),
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
