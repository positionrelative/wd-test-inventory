<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\AssetServiceInterface;
use App\Enums\AssetInclude;
use App\Exceptions\AssetInUseException;
use App\Models\Asset;
use App\Services\Support\CachedCounter;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class AssetService implements AssetServiceInterface
{
    /** Relations of the detail view. */
    private const DETAIL_RELATIONS = ['assetModel', 'manufacturer', 'contracts'];

    private const COUNT_KEY = 'assets';

    public function __construct(
        private readonly CachedCounter $counter,
    ) {
    }

    public function paginate(int $perPage, array $includes = []): CursorPaginator
    {
        return Asset::query()
            ->with(AssetInclude::relationsFor($includes))
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }

    public function count(): int
    {
        return $this->counter->remember(self::COUNT_KEY, static fn () => Asset::query()->count());
    }

    public function get(Asset $asset): Asset
    {
        return $asset->load(self::DETAIL_RELATIONS);
    }

    public function create(array $attributes): Asset
    {
        $asset = Asset::query()->create($attributes);
        $this->counter->forget(self::COUNT_KEY);

        return $this->get($asset);
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
        $this->counter->forget(self::COUNT_KEY);
    }
}
