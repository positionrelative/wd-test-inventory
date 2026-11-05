<?php

declare(strict_types=1);

namespace App\Http\Errors;

use App\Exceptions\ApiException;
use App\Http\Resources\ErrorResource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Turns any exception thrown during an API request into the standard error response.
 */
final class ApiExceptionRenderer
{
    public function __construct(
        #[Config('app.debug')]
        private readonly bool $debug = false,
    ) {
    }

    public function render(Throwable $e, Request $request): ?JsonResponse
    {
        // Deliberately built responses are passed through untouched.
        if ($e instanceof HttpResponseException) {
            return null;
        }

        return (new ErrorResource($this->toApiError($e)))->response($request);
    }

    public function toApiError(Throwable $e): ApiError
    {
        return match (true) {
            $e instanceof ApiException => new ApiError($e->status(), $e->errorCode(), $e->getMessage()),
            $e instanceof ValidationException => new ApiError(
                $e->status,
                'validation_failed',
                'The given data was invalid.',
                $e->errors(),
            ),
            $e instanceof AuthenticationException => new ApiError(
                Response::HTTP_UNAUTHORIZED,
                'unauthenticated',
                'Unauthenticated.',
            ),
            $e instanceof UniqueConstraintViolationException => $this->conflict(
                'The request conflicts with an existing record.',
            ),
            $e instanceof QueryException && $this->isIntegrityViolation($e) => $this->conflict(
                'The request violates a data integrity constraint.',
            ),
            $e instanceof HttpExceptionInterface => $this->fromHttpException($e),
            default => $this->serverError($e),
        };
    }

    private function fromHttpException(HttpExceptionInterface $e): ApiError
    {
        $status = $e->getStatusCode();
        $previous = $e instanceof Throwable ? $e->getPrevious() : null;

        $message = match (true) {
            $previous instanceof ModelNotFoundException => sprintf(
                '%s not found.',
                class_basename($previous->getModel()),
            ),
            $status === Response::HTTP_NOT_FOUND => 'The requested resource was not found.',
            $status < Response::HTTP_INTERNAL_SERVER_ERROR && $e instanceof Throwable && $e->getMessage() !== ''
                => $e->getMessage(),
            default => $this->statusText($status) . '.',
        };

        return new ApiError(
            $status,
            $this->codeFor($status),
            $message,
            headers: $e->getHeaders(),
        );
    }

    private function conflict(string $message): ApiError
    {
        return new ApiError(Response::HTTP_CONFLICT, 'conflict', $message);
    }

    private function serverError(Throwable $e): ApiError
    {
        return new ApiError(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'server_error',
            'An unexpected error occurred.',
            debug: $this->debug ? [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ] : null,
        );
    }

    /** SQLSTATE class 23 = integrity constraint violation (FK, unique, not null). */
    private function isIntegrityViolation(QueryException $e): bool
    {
        return str_starts_with((string) $e->getCode(), '23');
    }

    private function codeFor(int $status): string
    {
        return Str::slug($this->statusText($status), '_');
    }

    private function statusText(int $status): string
    {
        return Response::$statusTexts[$status] ?? 'Error';
    }
}
