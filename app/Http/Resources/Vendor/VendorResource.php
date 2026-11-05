<?php

declare(strict_types=1);

namespace App\Http\Resources\Vendor;

use App\Http\Resources\ApiResource;
use App\Models\Vendor;
use Illuminate\Http\Request;

/** @mixin Vendor */
final class VendorResource extends ApiResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
        ];
    }
}
