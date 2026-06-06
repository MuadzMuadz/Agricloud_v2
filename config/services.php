<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google OAuth (Integration-GoogleOAuth)
    |--------------------------------------------------------------------------
    |
    | Dipakai Socialite untuk login via Google (Opsi B / ID-token). `client_id`
    | juga jadi `aud` yang divalidasi saat memverifikasi id_token. `redirect`
    | hanya relevan bila kelak memakai Opsi A (Authorization Code).
    |
    */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Open-Meteo (Integration-Weather)
    |--------------------------------------------------------------------------
    |
    | Sumber data cuaca utama (tanpa API key). `base_url` untuk forecast,
    | `geocoding_url` untuk autocomplete kecamatan. Default lokasi dipakai bila
    | rantai resolusi (device -> manual -> lahan -> IP) gagal: Kejaksan, Cirebon.
    |
    */
    'weather' => [
        'base_url' => env('OPEN_METEO_BASE_URL', 'https://api.open-meteo.com/v1'),
        'geocoding_url' => env('OPEN_METEO_GEOCODING_URL', 'https://geocoding-api.open-meteo.com/v1'),
        'cache_ttl' => (int) env('WEATHER_CACHE_TTL', 1800), // detik (30 menit)
        'default_lat' => (float) env('WEATHER_DEFAULT_LAT', -6.7063),
        'default_lon' => (float) env('WEATHER_DEFAULT_LON', 108.5571),
        'default_district' => env('WEATHER_DEFAULT_DISTRICT', 'Kejaksan, Cirebon'),
    ],

];
