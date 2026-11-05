<?php

declare(strict_types=1);

namespace App\OpenApi;

use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;

/**
 * OpenAPI description of the standard error response (see ApiExceptionRenderer / ErrorResource):
 * {"error": {"status", "code", "message", "errors"?, "request_id"}}
 */
final class ErrorEnvelope
{
    public static function response(
        int $status,
        string $code,
        string $description,
        string $exampleMessage,
        bool $withFieldErrors = false,
    ): Response {
        $error = (new ObjectType())
            ->addProperty('status', (new IntegerType())->example($status))
            ->addProperty('code', (new StringType())->example($code)->setDescription('Stable, machine-readable error code.'))
            ->addProperty('message', (new StringType())->example($exampleMessage))
            ->addProperty('request_id', (new StringType())->format('uuid')->setDescription('Same as the X-Request-Id response header.'))
            ->setRequired(['status', 'code', 'message', 'request_id']);

        if ($withFieldErrors) {
            $error->addProperty(
                'errors',
                (new ObjectType())
                    ->setDescription('Messages per field that failed validation.')
                    ->additionalProperties((new ArrayType())->setItems(new StringType())),
            )->addRequired(['errors']);
        }

        $body = (new ObjectType())
            ->addProperty('error', $error)
            ->setRequired(['error']);

        return Response::make($status)
            ->setDescription($description)
            ->setContent('application/json', Schema::fromType($body));
    }
}
