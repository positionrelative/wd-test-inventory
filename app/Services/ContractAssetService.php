<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\ContractAssetServiceInterface;
use App\Enums\AssetInclude;
use App\Exceptions\AssetAlreadyAttachedException;
use App\Exceptions\AssetNotAttachedException;
use App\Models\Asset;
use App\Models\Contract;
use App\Services\Support\CachedCounter;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\UniqueConstraintViolationException;

final class ContractAssetService implements ContractAssetServiceInterface
{
    public function __construct(
        private readonly CachedCounter $counter,
    ) {
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
