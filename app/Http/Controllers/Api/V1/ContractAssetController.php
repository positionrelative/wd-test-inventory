<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\ContractServiceInterface;
use App\Exceptions\AssetAlreadyAttachedException;
use App\Exceptions\AssetNotAttachedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetIndexRequest;
use App\Http\Resources\Asset\AssetSummaryResource;
use App\Http\Resources\CountResource;
use App\Models\Asset;
use App\Models\Contract;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Contract assets', 'Attaching assets to contracts and listing them.', weight: 4)]
final class ContractAssetController extends Controller
{
    public function __construct(
        private readonly ContractServiceInterface $contracts,
    ) {
    }

    /**
     * List assets of a contract
     *
     * Same rows and parameters as `GET /assets` (cursor pagination, `?include=`),
     * limited to the assets attached to the contract.
     */
    public function index(AssetIndexRequest $request, Contract $contract): AnonymousResourceCollection
    {
        return AssetSummaryResource::collection(
            $this->contracts->paginateAssets($contract, $request->perPage(), $request->includes()),
        );
    }

    /**
     * Count assets of a contract
     *
     * Number of assets attached to the contract. Cached briefly; attaching and
     * detaching through the API show up immediately.
     */
    public function count(Contract $contract): CountResource
    {
        return new CountResource($this->contracts->countAssets($contract));
    }

    /**
     * Attach an asset
     *
     * Returns `201` with the attached asset. If the asset is already on the contract,
     * nothing changes and the response is `409 asset_already_attached`.
     *
     * @throws AssetAlreadyAttachedException
     */
    public function attach(Contract $contract, Asset $asset): JsonResponse
    {
        $this->contracts->attachAsset($contract, $asset);

        return AssetSummaryResource::make($asset)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Detach an asset
     *
     * Returns `404` with code `asset_not_attached` when the asset is not on the contract.
     *
     * @throws AssetNotAttachedException
     */
    public function detach(Contract $contract, Asset $asset): Response
    {
        $this->contracts->detachAsset($contract, $asset);

        return response()->noContent();
    }
}
