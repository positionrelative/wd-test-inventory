<?php

declare(strict_types=1);

namespace App\Http\Resources\Manufacturer;

use App\Http\Resources\ApiResource;
use App\Models\Manufacturer;
use Illuminate\Http\Request;

/** @mixin Manufacturer */
final class ManufacturerResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
