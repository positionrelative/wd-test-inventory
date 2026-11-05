<?php

declare(strict_types=1);

namespace App\Http\Resources\Asset;

use App\Http\Resources\ApiResource;
use App\Http\Resources\AssetModel\AssetModelResource;
use App\Http\Resources\Manufacturer\ManufacturerResource;
use App\Models\Asset;
use Illuminate\Http\Request;

/**
 * Asset row of the list: minimal fields. Related data only on request
 * (?include=model,manufacturer), as sibling objects (model and manufacturer
 * side by side, not nested). Only the requested relations are loaded.
 *
 * @mixin Asset
 */
final class AssetSummaryResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_tag' => $this->asset_tag,
            'serial_number' => $this->serial_number,
            'model' => AssetModelResource::make($this->whenLoaded('assetModel')),
            'manufacturer' => ManufacturerResource::make($this->whenLoaded('manufacturer')),
        ];
    }
}
