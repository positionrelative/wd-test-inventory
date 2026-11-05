<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\AbstractCursorPaginator;
use Illuminate\Support\Arr;

/**
 * Compact pagination meta, without the navigation URLs ("links", "meta.links"):
 * - cursor paginator: {"meta": {per_page, next_cursor, prev_cursor}}; next page: ?cursor=<next_cursor>
 * - length-aware paginator: {"meta": {current_page, per_page, total, last_page}}
 */
final class PaginatedResourceCollection extends AnonymousResourceCollection
{
    /**
     * @param array<string, mixed> $paginated
     * @param array<string, mixed> $default
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        $fields = $this->resource instanceof AbstractCursorPaginator
            ? ['per_page', 'next_cursor', 'prev_cursor']
            : ['current_page', 'per_page', 'total', 'last_page'];

        return [
            'meta' => Arr::only($paginated, $fields),
        ];
    }
}
