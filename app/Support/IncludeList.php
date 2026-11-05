<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The ?include=a,b query value.
 */
final class IncludeList
{
    /**
     * Splits the raw "a,b" value into unique, trimmed, non-empty names.
     *
     * @return list<string>
     */
    public static function parse(mixed $value): array
    {
        if (!is_string($value)) {
            return [];
        }

        $names = array_filter(array_map('trim', explode(',', $value)), static fn (string $name) => $name !== '');

        return array_values(array_unique($names));
    }
}
