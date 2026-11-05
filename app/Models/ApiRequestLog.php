<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * One API request / response pair, written by the LogApiTraffic middleware.
 */
#[Fillable([
    'request_id',
    'method',
    'path',
    'query',
    'ip',
    'user_agent',
    'request_headers',
    'request_body',
    'response_status',
    'response_headers',
    'response_body',
    'duration_ms',
])]
class ApiRequestLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'query' => 'json',
            'request_headers' => 'json',
            'request_body' => 'json',
            'response_status' => 'integer',
            'response_headers' => 'json',
            'response_body' => 'json',
            'duration_ms' => 'float',
        ];
    }

    /** @return Builder<static> */
    public function prunable(): Builder
    {
        return static::query()->where(
            'created_at',
            '<',
            now()->subDays((int) config('api_logging.retention_days', 30)),
        );
    }
}
