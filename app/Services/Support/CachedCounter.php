<?php

declare(strict_types=1);

namespace App\Services\Support;

use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Str;

/**
 * Caches COUNT(*) results: under heavy load a full count on every request is
 * too expensive. Writers call forget() so their own changes show up at once;
 * other changes appear within the TTL.
 */
final class CachedCounter
{
    private const PREFIX = 'count:';

    private const GENERATION_TTL = 86400;

    public function __construct(
        private readonly Repository $cache,
        #[Config('api.count_cache_ttl', 60)]
        private readonly int $ttl = 60,
    ) {
    }

    /** @param Closure(): int $count */
    public function remember(string $key, Closure $count): int
    {
        return (int) $this->cache->remember($this->valueKey($key), $this->ttl, $count);
    }

    public function forget(string $key): void
    {
        $this->cache->put($this->generationKey($key), $this->newGeneration(), self::GENERATION_TTL);
    }

    private function valueKey(string $key): string
    {
        $generation = $this->cache->remember(
            $this->generationKey($key),
            self::GENERATION_TTL,
            $this->newGeneration(...),
        );

        return self::PREFIX . $key . ':' . $generation;
    }

    private function generationKey(string $key): string
    {
        return self::PREFIX . $key . ':generation';
    }

    private function newGeneration(): string
    {
        return Str::random(16);
    }
}
