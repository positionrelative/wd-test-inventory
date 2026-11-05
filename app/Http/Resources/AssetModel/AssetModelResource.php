<?php

declare(strict_types=1);

namespace App\Http\Resources\AssetModel;

use App\Http\Resources\ApiResource;
use App\Models\AssetModel;
use Illuminate\Http\Request;

/** @mixin AssetModel */
final class AssetModelResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'model_number' => $this->model_number,
        ];
    }
}
