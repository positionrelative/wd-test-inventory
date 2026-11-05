<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use stdClass;
use Tests\TestCase;

final class CacheSerializationTest extends TestCase
{
    private Repository $store;

    protected function setUp(): void
    {
        parent::setUp();

        // Serializes like the Redis store, without needing Redis in tests.
        $this->store = Cache::store('file');
    }

    protected function tearDown(): void
    {
        $this->store->forget('pulse-card');
        $this->store->forget('other');

        parent::tearDown();
    }

    /** Regression: Pulse caches card results like this; they must come back intact, not as __PHP_Incomplete_Class. */
    public function test_pulse_card_results_survive_a_serializing_cache_store(): void
    {
        $row = new stdClass();
        $row->class = 'RuntimeException';
        $row->latest = CarbonImmutable::parse('2026-11-05 12:00:00');

        $this->store->put('pulse-card', [collect([$row]), 1.5], 60);
        [$rows] = $this->store->get('pulse-card');

        $this->assertInstanceOf(Collection::class, $rows);
        $this->assertInstanceOf(stdClass::class, $rows->first());
        $this->assertInstanceOf(CarbonImmutable::class, $rows->first()->latest);
        $this->assertSame('RuntimeException', $rows->first()->class);
    }

    public function test_other_classes_are_still_not_unserialized(): void
    {
        $this->store->put('other', new \ArrayObject([1]), 60);

        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $this->store->get('other'));
    }

    public function test_no_cache_store_writes_to_the_database(): void
    {
        foreach (config('cache.stores') as $name => $store) {
            $this->assertNotSame('database', $store['driver'], "cache store [{$name}] uses the database driver");
        }

        $this->assertInstanceOf(RedisStore::class, Cache::store('database')->getStore());
    }
}
