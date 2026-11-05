<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Pagination\Cursor;

/**
 * The ?cursor= value must be a cursor issued by the API (meta.next_cursor /
 * meta.prev_cursor). Without this check Laravel would silently fall back to
 * the first page.
 */
final class DecodableCursor implements ValidationRule
{
    public const MESSAGE = 'The cursor is invalid.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || Cursor::fromEncoded($value) === null) {
            $fail(self::MESSAGE);
        }
    }
}
