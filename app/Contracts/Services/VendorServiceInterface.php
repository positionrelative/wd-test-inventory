<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface VendorServiceInterface
{
    /** @return LengthAwarePaginator<int, Vendor> Ordered by name. */
    public function paginate(int $perPage): LengthAwarePaginator;
}
