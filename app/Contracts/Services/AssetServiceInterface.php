<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Exceptions\AssetInUseException;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AssetServiceInterface
{
    /** @return LengthAwarePaginator<int, Asset> */
    public function paginate(int $perPage): LengthAwarePaginator;

    /** @return Asset */
    public function get(Asset $asset): Asset;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Asset;

    /** @param array<string, mixed> $attributes */
    public function update(Asset $asset, array $attributes): Asset;

    /** @throws AssetInUseException When the asset is on a contract or has assignments. */
    public function delete(Asset $asset): void;
}
