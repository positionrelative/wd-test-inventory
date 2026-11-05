<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\AssetServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetIndexRequest;
use App\Http\Requests\AssetRequest;
use App\Http\Resources\Asset\AssetResource;
use App\Http\Resources\Asset\AssetSummaryResource;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class AssetController extends Controller
{
    public function __construct(
        private readonly AssetServiceInterface $assets,
    ) {
    }

    /** Minimal rows for a table; ?include=model,manufacturer adds flat related columns. */
    public function index(AssetIndexRequest $request): AnonymousResourceCollection
    {
        return AssetSummaryResource::collection(
            $this->assets->paginate($request->perPage(), $request->includes()),
        );
    }

    public function store(AssetRequest $request): JsonResponse
    {
        return AssetResource::make($this->assets->create($request->validated()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Asset $asset): AssetResource
    {
        return AssetResource::make($this->assets->get($asset));
    }

    public function update(AssetRequest $request, Asset $asset): AssetResource
    {
        return AssetResource::make($this->assets->update($asset, $request->validated()));
    }

    public function destroy(Asset $asset): Response
    {
        $this->assets->delete($asset);

        return response()->noContent();
    }
}
