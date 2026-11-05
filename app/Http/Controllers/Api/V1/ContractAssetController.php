<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Services\ContractServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Resources\Contract\ContractResource;
use App\Models\Asset;
use App\Models\Contract;
use Illuminate\Http\Response;

/**
 * Assets on a contract: /api/contracts/{contract}/assets/{asset}.
 */
final class ContractAssetController extends Controller
{
    public function __construct(
        private readonly ContractServiceInterface $contracts,
    ) {
    }

    /** PUT: attach the asset (idempotent) and return the contract with its assets. */
    public function attach(Contract $contract, Asset $asset): ContractResource
    {
        return ContractResource::make($this->contracts->attachAsset($contract, $asset));
    }

    /** DELETE: detach the asset from the contract. */
    public function detach(Contract $contract, Asset $asset): Response
    {
        $this->contracts->detachAsset($contract, $asset);

        return response()->noContent();
    }
}
