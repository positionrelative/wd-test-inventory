<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Manufacturer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ManufacturerServiceInterface
{
    /** @return LengthAwarePaginator<int, Manufacturer> Ordered by name. */
    public function paginate(int $perPage): LengthAwarePaginator;
}
