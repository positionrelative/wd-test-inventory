<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\ContractServiceInterface;
use App\Enums\AssetInclude;
use App\Exceptions\AssetNotAttachedException;
use App\Exceptions\ContractHasAssetsException;
use App\Models\Asset;
use App\Models\Contract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ContractService implements ContractServiceInterface
{
    /** Relations of the list view. */
    private const LIST_RELATIONS = ['vendor'];

    /** Relations of the detail view: the list ones plus the attached assets. */
    private const DETAIL_RELATIONS = [...self::LIST_RELATIONS, 'assets.assetModel', 'assets.manufacturer'];

    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Contract::query()
            ->with(self::LIST_RELATIONS)
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function get(Contract $contract): Contract
    {
        return $contract->load(self::DETAIL_RELATIONS);
    }

    public function create(array $attributes): Contract
    {
        return $this->get(Contract::query()->create($attributes));
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
    }

    public function paginateAssets(Contract $contract, int $perPage, array $includes = []): LengthAwarePaginator
    {
        // Served by the pivot's contract_id index (InnoDB appends the PK: contract_id, asset_id).
        return $contract->assets()
            ->with(AssetInclude::relationsFor($includes))
            ->orderBy('assets.id')
            ->paginate($perPage);
    }

    public function attachAsset(Contract $contract, Asset $asset): void
    {
        $contract->assets()->syncWithoutDetaching([$asset->getKey()]);
    }

    public function detachAsset(Contract $contract, Asset $asset): void
    {
        if ($contract->assets()->detach($asset->getKey()) === 0) {
            throw AssetNotAttachedException::for($contract, $asset);
        }
    }
}
