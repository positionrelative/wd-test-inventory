<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AssetInclude;
use Closure;

final class AssetIndexRequest extends IndexRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'include' => ['sometimes', 'bail', 'string', $this->knownIncludes(...)],
        ];
    }

    /** @return list<AssetInclude> */
    public function includes(): array
    {
        return AssetInclude::fromRequest($this);
    }

    private function knownIncludes(string $attribute, mixed $value, Closure $fail): void
    {
        $unknown = array_diff(AssetInclude::parse($value), AssetInclude::values());

        if ($unknown !== []) {
            $fail(sprintf(
                'Unknown include: %s. Allowed: %s.',
                implode(', ', $unknown),
                implode(', ', AssetInclude::values()),
            ));
        }
    }
}
