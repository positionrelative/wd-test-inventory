<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Asset;
use App\Models\Contract;
use Symfony\Component\HttpFoundation\Response;

final class AssetNotAttachedException extends ApiException
{
    public static function for(Contract $contract, Asset $asset): self
    {
        return new self(sprintf(
            'Asset #%d is not attached to contract #%d.',
            $asset->getKey(),
            $contract->getKey(),
        ));
    }

    public function status(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function errorCode(): string
    {
        return 'asset_not_attached';
    }
}
