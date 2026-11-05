<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Asset;
use Symfony\Component\HttpFoundation\Response;

final class AssetInUseException extends ApiException
{
    public static function for(Asset $asset): self
    {
        return new self(sprintf(
            'Asset #%d cannot be deleted while it is attached to a contract or has assignments.',
            $asset->getKey(),
        ));
    }

    public function status(): int
    {
        return Response::HTTP_CONFLICT;
    }

    public function errorCode(): string
    {
        return 'asset_in_use';
    }
}
