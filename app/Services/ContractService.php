<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\ContractServiceInterface;
use App\Enums\AssetInclude;
use App\Exceptions\AssetAlreadyAttachedException;
use App\Exceptions\AssetNotAttachedException;
use App\Exceptions\ContractHasAssetsException;
use App\Models\Asset;
use App\Models\Contract;
use App\Services\Support\CachedCounter;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\UniqueConstraintViolationException;

final class ContractService implements ContractServiceInterface
{
    /** Relations of the list view. */
    private const LIST_RELATIONS = ['vendor'];

    private const COUNT_KEY = 'contracts';

    public function __construct(
        private readonly CachedCounter $counter,
        #[Config('api.contract_assets_preview', 15)]
        private readonly int $assetsPreview = 15,
    ) {
    }

    public function paginate(int $perPage): CursorPaginator
    {
        return Contract::query()
            ->with(self::LIST_RELATIONS)
            ->orderBy('id')
            ->cursorPaginate($perPage);
    }

    public function count(): int
    {
        return $this->counter->remember(self::COUNT_KEY, static fn () => Contract::query()->count());
    }

    /**
     * Detail view: vendor, the number of assets and only the first few of them,
     * so the response stays bounded for contracts with many assets.
     */
    public function get(Contract $contract): Contract
    {
        return $contract
            ->load([
                ...self::LIST_RELATIONS,
                'assets' => fn (BelongsToMany $query) => $query
                    ->with(['assetModel', 'manufacturer'])
                    ->orderBy('assets.id')
                    ->limit($this->assetsPreview),
            ])
            ->loadCount('assets');
    }

    public function create(array $attributes): Contract
    {
        $contract = Contract::query()->create($attributes);
        $this->counter->forget(self::COUNT_KEY);

        return $this->get($contract);
    }

    public function update(Contract $contract, array $attributes): Contract
    {
        $contract->update($attributes);

        return $this->get($contract);
    }

    public function delete(Contract $contract): void
    {
        // Checked up front for a clear error; the FK constraints still guard against races.
        if ($contract->assets()->exists()) {
            throw ContractHasAssetsException::for($contract);
        }

        $contract->delete();
        $this->counter->forget(self::COUNT_KEY);
        $this->counter->forget($this->assetsCountKey($contract));
    }

    public function paginateAssets(Contract $contract, int $perPage, array $includes = []): CursorPaginator
    {
        // Served by the pivot's contract_id index (InnoDB appends the PK: contract_id, asset_id).
        return $contract->assets()
            ->with(AssetInclude::relationsFor($includes))
            ->orderBy('assets.id')
            ->cursorPaginate($perPage);
    }

    public function countAssets(Contract $contract): int
    {
        return $this->counter->remember(
            $this->assetsCountKey($contract),
            static fn () => $contract->assets()->count(),
        );
    }

    public function attachAsset(Contract $contract, Asset $asset): void
    {
        // Plain INSERT, no read of the existing links: the pivot's primary key
        // (asset_id, contract_id) rejects duplicates, concurrent ones included.
        // The savepoint keeps an outer transaction usable after the violation.
        try {
            $contract->getConnection()->transaction(
                static fn () => $contract->assets()->attach($asset->getKey()),
            );
        } catch (UniqueConstraintViolationException) {
            throw AssetAlreadyAttachedException::for($contract, $asset);
        }

        $this->counter->forget($this->assetsCountKey($contract));
    }

    public function detachAsset(Contract $contract, Asset $asset): void
    {
        if ($contract->assets()->detach($asset->getKey()) === 0) {
            throw AssetNotAttachedException::for($contract, $asset);
        }

        $this->counter->forget($this->assetsCountKey($contract));
    }

    private function assetsCountKey(Contract $contract): string
    {
        return "contracts.{$contract->getKey()}.assets";
    }
}
