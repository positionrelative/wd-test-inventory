<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\VendorServiceInterface;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class VendorService implements VendorServiceInterface
{
    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Vendor::query()
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);
    }
}
