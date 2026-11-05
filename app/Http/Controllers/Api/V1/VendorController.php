<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\VendorServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexRequest;
use App\Http\Resources\CountResource;
use App\Http\Resources\Vendor\VendorResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class VendorController extends Controller
{
    public function __construct(
        private readonly VendorServiceInterface $vendors,
    ) {
    }

    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        return VendorResource::collection($this->vendors->paginate($request->perPage()));
    }

    public function count(): CountResource
    {
        return new CountResource($this->vendors->count());
    }
}
