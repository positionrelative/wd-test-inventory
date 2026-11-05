<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\AssetModel;
use Symfony\Component\HttpFoundation\Response;

final class AssetModelInUseException extends ApiException
{
    public static function for(AssetModel $model): self
    {
        return new self(sprintf(
            'Asset model #%d cannot be deleted while assets use it.',
            $model->getKey(),
        ));
    }

    public function status(): int
    {
        return Response::HTTP_CONFLICT;
    }

    public function errorCode(): string
    {
        return 'asset_model_in_use';
    }
}
