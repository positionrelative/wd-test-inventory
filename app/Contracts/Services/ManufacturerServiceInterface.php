<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Manufacturer;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface ManufacturerServiceInterface
{
    /** @return CursorPaginator<int, Manufacturer> Ordered by name, cursor paginated. */
    public function paginate(int $perPage): CursorPaginator;

    /** Number of manufacturers (cached, see config api.count_cache_ttl). */
    public function count(): int;
}
