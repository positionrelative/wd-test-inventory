<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\IncludeList;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ?include=a,b may only name the allowed includes.
 */
final class KnownIncludes implements ValidationRule
{
    /** @param list<string> $allowed */
    public function __construct(
        private readonly array $allowed,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $unknown = array_diff(IncludeList::parse($value), $this->allowed);

        if ($unknown !== []) {
            $fail(sprintf(
                'Unknown include: %s. Allowed: %s.',
                implode(', ', $unknown),
                implode(', ', $this->allowed),
            ));
        }
    }
}
