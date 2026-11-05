<?php

declare(strict_types=1);

namespace App\Http\Resources\Asset;

use App\Http\Resources\ApiResource;
use App\Http\Resources\AssetModel\AssetModelResource;
use App\Http\Resources\Contract\ContractSummaryResource;
use App\Http\Resources\Manufacturer\ManufacturerResource;
use App\Models\Asset;
use Illuminate\Http\Request;

/**
 * Asset detail: relations (model, manufacturer, contracts) and timestamps.
 *
 * @mixin Asset
 */
final class AssetResource extends ApiResource
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
            'contracts' => ContractSummaryResource::collection($this->whenLoaded('contracts')),
            'attached_at' => $this->whenPivotLoaded('asset_contract', fn () => $this->pivot->created_at),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
