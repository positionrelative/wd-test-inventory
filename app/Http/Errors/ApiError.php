<?php

declare(strict_types=1);

namespace App\Http\Errors;

/**
 * Transport-neutral description of an API error, rendered by ErrorResource.
 */
final readonly class ApiError
{
    /**
     * @param array<string, list<string>>|null $errors Field-level validation messages.
     * @param array<string, string> $headers Extra response headers (e.g. Retry-After, Allow).
     * @param array<string, mixed>|null $debug Diagnostic details, only when app.debug is on.
     */
    public function __construct(
        public int $status,
        public string $code,
        public string $message,
        public ?array $errors = null,
        public array $headers = [],
        public ?array $debug = null,
    ) {
    }
}
