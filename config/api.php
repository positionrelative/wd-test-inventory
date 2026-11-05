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

];
