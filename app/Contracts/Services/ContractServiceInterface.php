<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Enums\AssetInclude;
use App\Exceptions\AssetAlreadyAttachedException;
use App\Exceptions\AssetNotAttachedException;
use App\Exceptions\ContractHasAssetsException;
use App\Models\Asset;
use App\Models\Contract;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface ContractServiceInterface
{
    /** @return CursorPaginator<int, Contract> List view: without assets, cursor paginated. */
    public function paginate(int $perPage): CursorPaginator;

    public function count(): int;

    /** @return Contract with its assets, for the detail view. */
    public function get(Contract $contract): Contract;

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Contract;

    /** @param array<string, mixed> $attributes */
    public function update(Contract $contract, array $attributes): Contract;

    /** @throws ContractHasAssetsException When assets are still attached. */
    public function delete(Contract $contract): void;

    /**
     * Assets on the contract, cursor paginated, for contracts with many assets.
     *
     * @param list<AssetInclude> $includes
     * @return CursorPaginator<int, Asset>
     */
    public function paginateAssets(Contract $contract, int $perPage, array $includes = []): CursorPaginator;

    public function countAssets(Contract $contract): int;

    /** @throws AssetAlreadyAttachedException When the asset is already on the contract. */
    public function attachAsset(Contract $contract, Asset $asset): void;

    /** @throws AssetNotAttachedException When the asset is not on the contract. */
    public function detachAsset(Contract $contract, Asset $asset): void;
}
