<?php

declare(strict_types=1);

namespace App\Http\Resources\Contract;

use App\Http\Resources\ApiResource;
use App\Http\Resources\Asset\AssetSummaryResource;
use App\Http\Resources\Vendor\VendorResource;
use App\Models\Contract;
use Illuminate\Http\Request;

/** @mixin Contract */
final class ContractResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'contract_number' => $this->contract_number,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'vendor' => VendorResource::make($this->whenLoaded('vendor')),
            /** Only in the contract detail. */
            'assets_count' => $this->whenCounted('assets'),
            /** Only in the contract detail; the first assets only, all of them: `GET /contracts/{contract}/assets`. */
            'assets' => AssetSummaryResource::collection($this->whenLoaded('assets')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
