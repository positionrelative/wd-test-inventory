<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\ManufacturerServiceInterface;
use App\Models\Manufacturer;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class ManufacturerService implements ManufacturerServiceInterface
{
    public function paginate(int $perPage): CursorPaginator
    {
        // name is unique (indexed); id keeps the cursor order deterministic.
        return Manufacturer::query()
            ->orderBy('name')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }
}
