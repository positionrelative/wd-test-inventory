<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AssetModel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AssetModel */
final class AssetModelResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'model_number' => $this->model_number,
            'manufacturer' => new ManufacturerResource($this->whenLoaded('manufacturer')),
        ];
    }
}
