<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\ContractServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetIndexRequest;
use App\Http\Resources\Asset\AssetSummaryResource;
use App\Models\Asset;
use App\Models\Contract;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Assets on a contract: /api/v1/contracts/{contract}/assets[/{asset}].
 */
final class ContractAssetController extends Controller
{
    public function __construct(
        private readonly ContractServiceInterface $contracts,
    ) {
    }

    public function index(AssetIndexRequest $request, Contract $contract): AnonymousResourceCollection
    {
        return AssetSummaryResource::collection(
            $this->contracts->paginateAssets($contract, $request->perPage(), $request->includes()),
        );
    }

    /** PUT: attach the asset (idempotent). */
    public function attach(Contract $contract, Asset $asset): Response
    {
        $this->contracts->attachAsset($contract, $asset);

        return response()->noContent();
    }

    /** DELETE: detach the asset from the contract. */
    public function detach(Contract $contract, Asset $asset): Response
    {
        $this->contracts->detachAsset($contract, $asset);

        return response()->noContent();
    }
}
