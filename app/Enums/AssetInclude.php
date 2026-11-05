<?php

declare(strict_types=1);

namespace App\Enums;

use App\Support\IncludeList;
use Illuminate\Http\Request;

/**
 * Optional relations of asset lists (?include=model,manufacturer,contracts):
 * GET /api/v1/assets and GET /api/v1/contracts/{contract}/assets
 */
enum AssetInclude: string
{
    case Model = 'model';
    case Manufacturer = 'manufacturer';
    case Contracts = 'contracts';

    /** @return list<string> Eloquent relations to eager load (no N+1). */
    public function relations(): array
    {
        return match ($this) {
            self::Model => ['assetModel'],
            self::Manufacturer => ['manufacturer'],
            self::Contracts => ['contracts'],
        };
    }

    /**
     * @param list<self> $includes
     * @return list<string> All relations to eager load for the given includes.
     */
    public static function relationsFor(array $includes): array
    {
        return array_values(array_unique(array_merge(
            [],
            ...array_map(static fn (self $include) => $include->relations(), $includes),
        )));
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Known includes requested by the client; unknown names are rejected
     * earlier by the KnownIncludes rule.
     *
     * @return list<self>
     */
    public static function fromRequest(Request $request): array
    {
        return array_values(array_filter(array_map(
            self::tryFrom(...),
            IncludeList::parse($request->query('include')),
        )));
    }
}
