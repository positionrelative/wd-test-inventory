<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Exceptions\AssetModelInUseException;
use App\Models\AssetModel;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface AssetModelServiceInterface
{
    /** @return CursorPaginator<int, AssetModel> Ordered by name, with the manufacturer. */
    public function paginate(int $perPage): CursorPaginator;

    /** Number of asset models (cached, see config api.count_cache_ttl). */
    public function count(): int;

    /** Asset model with its manufacturer. */
    public function get(AssetModel $model): AssetModel;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): AssetModel;

    /** @param array<string, mixed> $attributes */
    public function update(AssetModel $model, array $attributes): AssetModel;

    /** @throws AssetModelInUseException When assets use the model. */
    public function delete(AssetModel $model): void;
}
