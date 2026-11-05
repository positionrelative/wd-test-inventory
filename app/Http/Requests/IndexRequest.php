<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\DecodableCursor;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared parameters of the cursor paginated list endpoints: ?per_page=&cursor=
 * Endpoint-specific list requests extend it with their own parameters.
 */
class IndexRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 15;

    public const MAX_PER_PAGE = 100;

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            /** @default 15 */
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
            /** `meta.next_cursor` or `meta.prev_cursor` of the previous page. */
            'cursor' => ['sometimes', 'bail', 'string', 'max:1024', new DecodableCursor()],
        ];
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', self::DEFAULT_PER_PAGE);
    }
}
