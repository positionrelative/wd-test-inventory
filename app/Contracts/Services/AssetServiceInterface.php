<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Enums\AssetInclude;
use App\Exceptions\AssetInUseException;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface AssetServiceInterface
{
    /**
     * @param list<AssetInclude> $includes
     * @return CursorPaginator<int, Asset>
     */
    public function paginate(int $perPage, array $includes = []): CursorPaginator;

    /** @return int Number of assets. */
    public function count(): int;

    /** Detail view: model, manufacturer and contracts loaded. */
    public function get(Asset $asset): Asset;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Asset;

    /** @param array<string, mixed> $attributes */
    public function update(Asset $asset, array $attributes): Asset;

    /** @throws AssetInUseException When the asset is on a contract or has assignments. */
    public function delete(Asset $asset): void;
}
