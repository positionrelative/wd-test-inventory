<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pagination\Cursor;

/**
 * Shared parameters of the cursor paginated list endpoints: ?per_page=&cursor=
 * Endpoint-specific list requests extend it with their own parameters.
 */
class IndexRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAX_PER_PAGE = 100;

    public const INVALID_CURSOR = 'The cursor is invalid.';

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
            // Opaque value from meta.next_cursor / meta.prev_cursor of the previous response.
            'cursor' => ['sometimes', 'bail', 'string', 'max:1024', $this->decodableCursor(...)],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', self::DEFAULT_PER_PAGE);
    }

    /** Without this check Laravel would silently fall back to the first page. */
    private function decodableCursor(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || Cursor::fromEncoded($value) === null) {
            $fail(self::INVALID_CURSOR);
        }
    }
}
