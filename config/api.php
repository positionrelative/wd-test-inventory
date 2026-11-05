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

];
