<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\ManufacturerServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexRequest;
use App\Http\Resources\CountResource;
use App\Http\Resources\Manufacturer\ManufacturerResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Manufacturers', 'Makers of the asset models. Read-only.', weight: 5)]
final class ManufacturerController extends Controller
{
    public function __construct(
        private readonly ManufacturerServiceInterface $manufacturers,
    ) {
    }

    /**
     * List manufacturers
     *
     * Ordered by name and cursor paginated, e.g. for filter or select fields.
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        return ManufacturerResource::collection($this->manufacturers->paginate($request->perPage()));
    }

    /**
     * Count manufacturers
     *
     * Total number of manufacturers. Cached briefly.
     */
    public function count(): CountResource
    {
        return new CountResource($this->manufacturers->count());
    }
}
