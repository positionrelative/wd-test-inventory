<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Contract;
use Symfony\Component\HttpFoundation\Response;

final class ContractHasAssetsException extends ApiException
{
    public static function for(Contract $contract): self
    {
        return new self(sprintf(
            'Contract #%d cannot be deleted while assets are attached to it. Detach the assets first.',
            $contract->getKey(),
        ));
    }

    public function status(): int
    {
        return Response::HTTP_CONFLICT;
    }

    public function errorCode(): string
    {
        return 'contract_has_assets';
    }
}
