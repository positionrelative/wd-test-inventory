<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\AssetServiceInterface;
use App\Exceptions\AssetInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetIndexRequest;
use App\Http\Requests\AssetRequest;
use App\Http\Resources\Asset\AssetResource;
use App\Http\Resources\Asset\AssetSummaryResource;
use App\Http\Resources\CountResource;
use App\Models\Asset;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Assets', 'Physical items of the inventory.', weight: 1)]
final class AssetController extends Controller
{
    public function __construct(
        private readonly AssetServiceInterface $assets,
    ) {
    }

    /**
     * List assets
     *
     * Minimal rows for tables, ordered by id. The list is cursor paginated:
     * pass `meta.next_cursor` as `?cursor=` to get the next page.
     *
     * Model, manufacturer and contracts are added only when requested with `?include=`.
     */
    public function index(AssetIndexRequest $request): AnonymousResourceCollection
    {
        return AssetSummaryResource::collection(
            $this->assets->paginate($request->perPage(), $request->includes()),
        );
    }

    /**
     * Count assets
     *
     * Total number of assets, e.g. for "1–15 of 1 234" style table footers.
     * Cached briefly; changes made through the API show up immediately.
     */
    public function count(): CountResource
    {
        return new CountResource($this->assets->count());
    }

    /**
     * Create an asset
     *
     * Returns the new asset in its detail form, with its model and manufacturer.
     */
    public function store(AssetRequest $request): JsonResponse
    {
        return AssetResource::make($this->assets->create($request->validated()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Get an asset
     *
     * The asset with its model, manufacturer and the contracts it is on.
     */
    public function show(Asset $asset): AssetResource
    {
        return AssetResource::make($this->assets->get($asset));
    }

    /**
     * Update an asset
     *
     * Both `PUT` and `PATCH` update partially: only the fields sent are changed.
     */
    public function update(AssetRequest $request, Asset $asset): AssetResource
    {
        return AssetResource::make($this->assets->update($asset, $request->validated()));
    }

    /**
     * Delete an asset
     *
     * An asset that is on a contract or has assignments cannot be deleted (`409 asset_in_use`);
     * detach it from its contracts first.
     *
     * @throws AssetInUseException
     */
    public function destroy(Asset $asset): Response
    {
        $this->assets->delete($asset);

        return response()->noContent();
    }
}
