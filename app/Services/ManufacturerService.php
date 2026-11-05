<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\ManufacturerServiceInterface;
use App\Models\Manufacturer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ManufacturerService implements ManufacturerServiceInterface
{
    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Manufacturer::query()
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);
    }
}
