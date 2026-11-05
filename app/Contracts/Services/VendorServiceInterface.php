<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Vendor;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface VendorServiceInterface
{
    /** @return CursorPaginator<int, Vendor> Ordered by name, cursor paginated. */
    public function paginate(int $perPage): CursorPaginator;
}
