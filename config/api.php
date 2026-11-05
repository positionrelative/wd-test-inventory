<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    |
    | Requests per minute per client (authenticated user, or IP address) on
    | every /api route. Exceeding it returns 429 with a Retry-After header.
    |
    */

    'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),

    /*
    |--------------------------------------------------------------------------
    | Count endpoints
    |--------------------------------------------------------------------------
    |
    | GET .../count results are cached for this many seconds. The API's own
    | writes (create, delete, attach, detach) clear the affected count at once.
    |
    */

    'count_cache_ttl' => (int) env('API_COUNT_CACHE_TTL', 60),

    // Assets embedded in a contract detail; the rest via GET /contracts/{contract}/assets.
    'contract_assets_preview' => (int) env('API_CONTRACT_ASSETS_PREVIEW', 15),

    /*
    |--------------------------------------------------------------------------
    | Internal tools: API documentation (/docs/api) and Pulse (/pulse)
    |--------------------------------------------------------------------------
    |
    | Protected by HTTP Basic Auth. Without both values they return 401.
    |
    */

    'docs' => [
        'username' => env('API_DOCS_USERNAME'),
        'password' => env('API_DOCS_PASSWORD'),
    ],

];
