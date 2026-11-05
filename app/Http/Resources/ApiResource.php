<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Base of the API data resources: their collections use the compact pagination format.
 */
abstract class ApiResource extends JsonResource
{
    /** @param mixed $resource */
    protected static function newCollection($resource): PaginatedResourceCollection
    {
        return new PaginatedResourceCollection($resource, static::class);
    }
}
