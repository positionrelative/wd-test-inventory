<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Number of items of a list: {"data": {"count": 1234}}.
 *
 * @property int $resource
 */
final class CountResource extends JsonResource
{
    public function __construct(int $count)
    {
        parent::__construct($count);
    }

    /** @return array<string, int> */
    public function toArray(Request $request): array
    {
        return [
            'count' => $this->resource,
        ];
    }
}
