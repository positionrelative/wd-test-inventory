<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Http\Request;

/**
 * Optional relations of the asset list: GET /api/v1/assets?include=model,manufacturer
 */
enum AssetInclude: string
{
    case Model = 'model';
    case Manufacturer = 'manufacturer';

    /** @return list<string> Eloquent relations to eager load (no N+1). */
    public function relations(): array
    {
        return match ($this) {
            self::Model => ['assetModel'],
            self::Manufacturer => ['manufacturer'],
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Splits the raw "a,b" query value into unique, trimmed names.
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

    /**
     * Known includes requested by the client; unknown names are rejected
     * earlier by AssetIndexRequest.
     *
     * @return list<self>
     */
    public static function fromRequest(Request $request): array
    {
        return array_values(array_filter(array_map(self::tryFrom(...), self::parse($request->query('include')))));
    }
}
