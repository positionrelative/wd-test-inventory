<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\AssetServiceInterface;
use App\Exceptions\AssetInUseException;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class AssetService implements AssetServiceInterface
{
    /** Relations returned with every asset. */
    private const RELATIONS = ['assetModel.manufacturer'];

    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Asset::query()
            ->with(self::RELATIONS)
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function get(Asset $asset): Asset
    {
        return $asset->load(self::RELATIONS);
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
        // Checked up front for a clear error; the FK constraints still guard against races.
        if ($asset->contracts()->exists() || $asset->assignments()->exists()) {
            throw AssetInUseException::for($asset);
        }

        $asset->delete();
    }
}
