<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\ManufacturerServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexRequest;
use App\Http\Resources\Manufacturer\ManufacturerResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ManufacturerController extends Controller
{
    public function __construct(
        private readonly ManufacturerServiceInterface $manufacturers,
    ) {
    }

    public function index(IndexRequest $request): AnonymousResourceCollection
    {
        return ManufacturerResource::collection($this->manufacturers->paginate($request->perPage()));
    }
}
