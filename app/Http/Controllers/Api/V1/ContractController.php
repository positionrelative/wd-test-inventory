<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\ContractServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContractRequest;
use App\Http\Requests\IndexRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class ContractController extends Controller
{
    public function __construct(
        private readonly ContractServiceInterface $contracts,
    ) {
    }

    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        return ContractResource::collection($this->contracts->paginate($request->perPage()));
    }

    public function store(ContractRequest $request): JsonResponse
    {
        return ContractResource::make($this->contracts->create($request->validated()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Contract $contract): ContractResource
    {
        return ContractResource::make($this->contracts->get($contract));
    }

    public function update(ContractRequest $request, Contract $contract): ContractResource
    {
        return ContractResource::make($this->contracts->update($contract, $request->validated()));
    }

    public function destroy(Contract $contract): Response
    {
        $this->contracts->delete($contract);

        return response()->noContent();
    }
}
