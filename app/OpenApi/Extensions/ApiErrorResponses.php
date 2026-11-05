<?php

declare(strict_types=1);

namespace App\OpenApi\Extensions;

use App\Exceptions\ApiException;
use App\OpenApi\ErrorEnvelope;
use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Documents the exceptions the API renders with the standard error envelope.
 * Registered after Scramble's built-in extensions, so it overrides their
 * Laravel-default ({"message", "errors"}) response bodies.
 */
final class ApiErrorResponses extends ExceptionToResponseExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType && (
            $type->isInstanceOf(ValidationException::class)
            || $type->isInstanceOf(ModelNotFoundException::class)
            || $type->isInstanceOf(NotFoundHttpException::class)
            || $type->isInstanceOf(ApiException::class)
        );
    }

    public function toResponse(Type $type): ?Response
    {
        if (!$type instanceof ObjectType) {
            return null;
        }

        return match (true) {
            $type->isInstanceOf(ValidationException::class) => ErrorEnvelope::response(
                422,
                'validation_failed',
                'Validation error',
                'The given data was invalid.',
                withFieldErrors: true,
            ),
            $type->isInstanceOf(ApiException::class) => $this->domainError($type->name),
            default => ErrorEnvelope::response(404, 'not_found', 'Not found', 'Asset not found.'),
        };
    }

    public function reference(ObjectType $type): Reference
    {
        $name = match (true) {
            $type->isInstanceOf(ValidationException::class) => 'ValidationError',
            $type->isInstanceOf(ApiException::class) => class_basename($type->name),
            default => 'NotFound',
        };

        return new Reference('responses', $name, $this->components);
    }

    /** Status and code are constants of each ApiException subclass. */
    private function domainError(string $class): Response
    {
        /** @var ApiException $exception */
        $exception = (new ReflectionClass($class))->newInstanceWithoutConstructor();

        return ErrorEnvelope::response(
            $exception->status(),
            $exception->errorCode(),
            sprintf('Domain error `%s`', $exception->errorCode()),
            sprintf('See error.code "%s".', $exception->errorCode()),
        );
    }
}
