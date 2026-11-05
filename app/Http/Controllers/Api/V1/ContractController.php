<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\ContractServiceInterface;
use App\Exceptions\ContractHasAssetsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContractRequest;
use App\Http\Requests\IndexRequest;
use App\Http\Resources\Contract\ContractResource;
use App\Http\Resources\CountResource;
use App\Models\Contract;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Contracts', 'Contracts with vendors and the assets they cover.', weight: 3)]
final class ContractController extends Controller
{
    public function __construct(
        private readonly ContractServiceInterface $contracts,
    ) {
    }

    /**
     * List contracts
     *
     * Contracts with their vendor, ordered by id and cursor paginated.
     * Assets are not part of the list; use the contract detail or
     * `GET /contracts/{contract}/assets`.
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        return ContractResource::collection($this->contracts->paginate($request->perPage()));
    }

    /**
     * Count contracts
     *
     * Total number of contracts. Cached briefly; changes made through the API
     * show up immediately.
     */
    public function count(): CountResource
    {
        return new CountResource($this->contracts->count());
    }

    /**
     * Create a contract
     *
     * The contract number must be unique per vendor, and the end date must not be
     * before the start date.
     */
    public function store(ContractRequest $request): JsonResponse
    {
        return ContractResource::make($this->contracts->create($request->validated()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Get a contract
     *
     * The contract with its vendor and all of its assets. For contracts with many
     * assets prefer the paginated `GET /contracts/{contract}/assets`.
     */
    public function show(Contract $contract): ContractResource
    {
        return ContractResource::make($this->contracts->get($contract));
    }

    /**
     * Update a contract
     *
     * Both `PUT` and `PATCH` update partially: only the fields sent are changed.
     * Dates and contract number uniqueness are checked together with the stored values.
     */
    public function update(ContractRequest $request, Contract $contract): ContractResource
    {
        return ContractResource::make($this->contracts->update($contract, $request->validated()));
    }

    /**
     * Delete a contract
     *
     * A contract with assets cannot be deleted (`409 contract_has_assets`);
     * detach its assets first.
     *
     * @throws ContractHasAssetsException
     */
    public function destroy(Contract $contract): Response
    {
        $this->contracts->delete($contract);

        return response()->noContent();
    }
}
