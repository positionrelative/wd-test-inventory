<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\VendorServiceInterface;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class VendorService implements VendorServiceInterface
{
    public function paginate(int $perPage): CursorPaginator
    {
        // name is unique (indexed); id keeps the cursor order deterministic.
        return Vendor::query()
            ->orderBy('name')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }
}
