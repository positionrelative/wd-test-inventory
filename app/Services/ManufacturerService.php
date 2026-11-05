<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\ManufacturerServiceInterface;
use App\Models\Manufacturer;
use App\Services\Support\CachedCounter;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class ManufacturerService implements ManufacturerServiceInterface
{
    private const COUNT_KEY = 'manufacturers';

    public function __construct(
        private readonly CachedCounter $counter,
    ) {
    }

    public function paginate(int $perPage): CursorPaginator
    {
        return Manufacturer::query()
            ->orderBy('name')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }

    public function count(): int
    {
        return $this->counter->remember(self::COUNT_KEY, static fn () => Manufacturer::query()->count());
    }
}
