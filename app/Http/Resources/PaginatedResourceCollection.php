<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Compact pagination: {"data": [...], "meta": {current_page, per_page, total, last_page}}.
 * The navigation URLs ("links", "meta.links") are left out; the client builds
 * them from page and last_page.
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
        return [
            'meta' => [
                'current_page' => $paginated['current_page'],
                'per_page' => $paginated['per_page'],
                'total' => $paginated['total'],
                'last_page' => $paginated['last_page'],
            ],
        ];
    }
}
