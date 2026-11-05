<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Exceptions\ContractHasAssetsException;
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
}
