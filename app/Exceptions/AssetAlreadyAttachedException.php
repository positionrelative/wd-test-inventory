<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Asset;
use App\Models\Contract;
use Symfony\Component\HttpFoundation\Response;

final class AssetAlreadyAttachedException extends ApiException
{
    public static function for(Contract $contract, Asset $asset): self
    {
        return new self(sprintf(
            'Asset #%d is already attached to contract #%d.',
            $asset->getKey(),
            $contract->getKey(),
        ));
    }

    public function status(): int
    {
        return Response::HTTP_CONFLICT;
    }

    public function errorCode(): string
    {
        return 'asset_already_attached';
    }
}
