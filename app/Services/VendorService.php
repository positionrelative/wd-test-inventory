<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\VendorServiceInterface;
use App\Models\Vendor;
use App\Services\Support\CachedCounter;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class VendorService implements VendorServiceInterface
{
    private const COUNT_KEY = 'vendors';

    public function __construct(
        private readonly CachedCounter $counter,
    ) {
    }

    public function paginate(int $perPage): CursorPaginator
    {
        return Vendor::query()
            ->orderBy('name')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }

    public function count(): int
    {
        return $this->counter->remember(self::COUNT_KEY, static fn () => Vendor::query()->count());
    }
}
