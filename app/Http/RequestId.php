<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Unique ID of a request: returned in the X-Request-Id header, in error
 * responses and stored in the API traffic log, so they can be matched up.
 */
final class RequestId
{
    public const HEADER = 'X-Request-Id';

    private const ATTRIBUTE = 'request_id';

    public static function assign(Request $request): string
    {
        $id = (string) Str::uuid();
        $request->attributes->set(self::ATTRIBUTE, $id);

        return $id;
    }

    public static function of(Request $request): ?string
    {
        $id = $request->attributes->get(self::ATTRIBUTE);

        return is_string($id) ? $id : null;
    }
}
