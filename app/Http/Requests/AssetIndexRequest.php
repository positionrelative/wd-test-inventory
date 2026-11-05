<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\AssetInclude;
use App\Rules\KnownIncludes;

final class AssetIndexRequest extends IndexRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            /**
             * Comma separated: `model`, `manufacturer`, `contracts`.
             *
             * @example model,manufacturer
             */
            'include' => ['sometimes', 'bail', 'string', new KnownIncludes(AssetInclude::values())],
        ];
    }

    /** @return list<AssetInclude> */
    public function includes(): array
    {
        return AssetInclude::fromRequest($this);
    }
}
