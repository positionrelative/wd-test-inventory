<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\ContractServiceInterface;
use App\Enums\AssetInclude;
use App\Exceptions\AssetNotAttachedException;
use App\Exceptions\ContractHasAssetsException;
use App\Models\Asset;
use App\Models\Contract;
use App\Services\Support\CachedCounter;
use Illuminate\Contracts\Pagination\CursorPaginator;

final class ContractService implements ContractServiceInterface
{
    /** Relations of the list view. */
    private const LIST_RELATIONS = ['vendor'];

    /** Relations of the detail view: the list ones plus the attached assets. */
    private const DETAIL_RELATIONS = [...self::LIST_RELATIONS, 'assets.assetModel', 'assets.manufacturer'];

    private const COUNT_KEY = 'contracts';

    public function __construct(
        private readonly CachedCounter $counter,
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

    public function get(Contract $contract): Contract
    {
        return $contract->load(self::DETAIL_RELATIONS);
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
        $changes = $contract->assets()->syncWithoutDetaching([$asset->getKey()]);

        if ($changes['attached'] !== []) {
            $this->counter->forget($this->assetsCountKey($contract));
        }
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
