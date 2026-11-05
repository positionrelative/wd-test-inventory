<?php

declare(strict_types=1);

namespace App\Services\Support;

use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Cache\Repository;

/**
 * Caches COUNT(*) results: under heavy load a full count on every request is
 * too expensive. Writers call forget() so their own changes show up at once;
 * other changes appear within the TTL.
 */
final class CachedCounter
{
    private const PREFIX = 'count:';

    public function __construct(
        private readonly Repository $cache,
        #[Config('api.count_cache_ttl', 60)]
        private readonly int $ttl = 60,
    ) {
    }

    /** @param Closure(): int $count */
    public function remember(string $key, Closure $count): int
    {
        return (int) $this->cache->remember(self::PREFIX . $key, $this->ttl, $count);
    }

    public function forget(string $key): void
    {
        $this->cache->forget(self::PREFIX . $key);
    }
}
