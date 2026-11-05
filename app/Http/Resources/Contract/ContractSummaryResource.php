<?php

declare(strict_types=1);

namespace App\Http\Resources\Contract;

use App\Http\Resources\ApiResource;
use App\Models\Contract;
use Illuminate\Http\Request;

/**
 * Compact contract, nested in other resources
 *
 * @mixin Contract
 */
final class ContractSummaryResource extends ApiResource
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
        ];
    }
}
