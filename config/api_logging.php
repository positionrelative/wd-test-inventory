<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API traffic logging
    |--------------------------------------------------------------------------
    |
    | Every request matching one of the paths is stored with its response in
    | the api_request_logs table. Sensitive headers and body fields are masked.
    |
    */

    'enabled' => (bool) env('API_LOGGING_ENABLED', true),

    'paths' => ['api', 'api/*'],

    'masked_headers' => [
        'authorization',
        'cookie',
        'set-cookie',
        'php-auth-pw',
        'x-api-key',
        'x-csrf-token',
        'x-xsrf-token',
    ],

    'masked_fields' => [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
    ],

    // Longer bodies are stored truncated.
    'max_body_length' => (int) env('API_LOGGING_MAX_BODY_LENGTH', 65535),

    // Entries older than this are removed by `php artisan model:prune`.
    'retention_days' => (int) env('API_LOGGING_RETENTION_DAYS', 30),

];
