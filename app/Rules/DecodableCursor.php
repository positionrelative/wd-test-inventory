<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The ?cursor= value must be a cursor as the API issues it (meta.next_cursor /
 * meta.prev_cursor): base64url JSON with a boolean direction flag and at least
 * one sort value, each a string or a number.
 */
final class DecodableCursor implements ValidationRule
{
    public const MESSAGE = 'The cursor is invalid.';

    private const DIRECTION_KEY = '_pointsToNextItems';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !$this->isValid($value)) {
            $fail(self::MESSAGE);
        }
    }

    private function isValid(string $encoded): bool
    {
        $json = base64_decode(strtr($encoded, '-_', '+/'), true);
        $data = $json === false ? null : json_decode($json, true);

        if (!is_array($data) || array_is_list($data) || !is_bool($data[self::DIRECTION_KEY] ?? null)) {
            return false;
        }

        unset($data[self::DIRECTION_KEY]);

        if ($data === []) {
            return false;
        }

        foreach ($data as $sortValue) {
            if (!is_string($sortValue) && !is_int($sortValue) && !is_float($sortValue)) {
                return false;
            }
        }

        return true;
    }
}
