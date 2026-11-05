<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\AssetServiceInterface;
use App\Enums\AssetInclude;
use App\Exceptions\AssetInUseException;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AssetService implements AssetServiceInterface
{
    /** Relations of the detail view. */
    private const DETAIL_RELATIONS = ['assetModel', 'manufacturer', 'contracts'];

    public function paginate(int $perPage, array $includes = []): LengthAwarePaginator
    {
        return Asset::query()
            ->with(AssetInclude::relationsFor($includes))
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function get(Asset $asset): Asset
    {
        return $asset->load(self::DETAIL_RELATIONS);
    }

    public function create(array $attributes): Asset
    {
        return $this->get(Asset::query()->create($attributes));
    }

    public function update(Asset $asset, array $attributes): Asset
    {
        $asset->update($attributes);

        return $this->get($asset);
    }

    public function delete(Asset $asset): void
    {
        if ($asset->contracts()->exists() || $asset->assignments()->exists()) {
            throw AssetInUseException::for($asset);
        }

        $asset->delete();
    }
}
