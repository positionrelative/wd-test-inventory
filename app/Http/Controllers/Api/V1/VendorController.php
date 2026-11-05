<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\VendorServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexRequest;
use App\Http\Resources\CountResource;
use App\Http\Resources\Vendor\VendorResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Vendors', 'Suppliers the contracts are made with. Read-only.', weight: 6)]
final class VendorController extends Controller
{
    public function __construct(
        private readonly VendorServiceInterface $vendors,
    ) {
    }

    /**
     * List vendors
     *
     * Ordered by name and cursor paginated, e.g. for picking the vendor of a contract.
     */
    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        return VendorResource::collection($this->vendors->paginate($request->perPage()));
    }

    /**
     * Count vendors
     *
     * Total number of vendors. Cached briefly.
     */
    public function count(): CountResource
    {
        return new CountResource($this->vendors->count());
    }
}
