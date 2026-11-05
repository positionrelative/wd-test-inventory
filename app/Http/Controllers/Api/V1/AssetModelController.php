<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\AssetModelServiceInterface;
use App\Exceptions\AssetModelInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetModelRequest;
use App\Http\Requests\IndexRequest;
use App\Http\Resources\AssetModel\AssetModelResource;
use App\Http\Resources\CountResource;
use App\Models\AssetModel;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

#[Group('Asset models', 'Types of assets (e.g. a laptop model), each made by a manufacturer.', weight: 2)]
final class AssetModelController extends Controller
{
    public function __construct(
        private readonly AssetModelServiceInterface $models,
    ) {
    }

    /**
     * List asset models
     *
     * Asset models with their manufacturer, ordered by name and cursor paginated,
     * e.g. for picking the model of a new asset.
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        return AssetModelResource::collection($this->models->paginate($request->perPage()));
    }

    /**
     * Count asset models
     *
     * Total number of asset models. Cached briefly; changes made through the API
     * show up immediately.
     */
    public function count(): CountResource
    {
        return new CountResource($this->models->count());
    }

    /**
     * Create an asset model
     *
     * The model number must be unique per manufacturer.
     */
    public function store(AssetModelRequest $request): JsonResponse
    {
        return AssetModelResource::make($this->models->create($request->validated()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Get an asset model
     *
     * The asset model with its manufacturer.
     */
    public function show(AssetModel $assetModel): AssetModelResource
    {
        return AssetModelResource::make($this->models->get($assetModel));
    }

    /**
     * Update an asset model
     *
     * Both `PUT` and `PATCH` update partially: only the fields sent are changed.
     * Model number uniqueness is checked together with the stored manufacturer.
     */
    public function update(AssetModelRequest $request, AssetModel $assetModel): AssetModelResource
    {
        return AssetModelResource::make($this->models->update($assetModel, $request->validated()));
    }

    /**
     * Delete an asset model
     *
     * A model that assets use cannot be deleted (`409 asset_model_in_use`).
     *
     * @throws AssetModelInUseException
     */
    public function destroy(AssetModel $assetModel): Response
    {
        $this->models->delete($assetModel);

        return response()->noContent();
    }
}
