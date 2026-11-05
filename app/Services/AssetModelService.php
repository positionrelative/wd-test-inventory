<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\AssetModelServiceInterface;
use App\Exceptions\AssetModelInUseException;
use App\Models\AssetModel;
use App\Services\Support\CachedCounter;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class AssetModelService implements AssetModelServiceInterface
{
    private const RELATIONS = ['manufacturer'];

    private const COUNT_KEY = 'asset_models';

    public function __construct(
        private readonly CachedCounter $counter,
    ) {
    }

    public function paginate(int $perPage): CursorPaginator
    {
        return AssetModel::query()
            ->with(self::RELATIONS)
            ->orderBy('name')
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }

    public function count(): int
    {
        return $this->counter->remember(self::COUNT_KEY, static fn () => AssetModel::query()->count());
    }

    public function get(AssetModel $model): AssetModel
    {
        return $model->load(self::RELATIONS);
    }

    public function create(array $attributes): AssetModel
    {
        $model = AssetModel::query()->create($attributes);
        $this->counter->forget(self::COUNT_KEY);

        return $this->get($model);
    }

    public function update(AssetModel $model, array $attributes): AssetModel
    {
        $model->update($attributes);

        return $this->get($model);
    }

    public function delete(AssetModel $model): void
    {
        // Checked up front for a clear error; the FK constraint still guards against races.
        if ($model->assets()->exists()) {
            throw AssetModelInUseException::for($model);
        }

        $model->delete();
        $this->counter->forget(self::COUNT_KEY);
    }
}
