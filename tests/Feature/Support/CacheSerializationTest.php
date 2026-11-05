<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use stdClass;
use Tests\TestCase;

final class CacheSerializationTest extends TestCase
{
    use RefreshDatabase;

    /** Regression: Pulse caches card results like this; they must come back intact, not as __PHP_Incomplete_Class. */
    public function test_pulse_card_results_survive_a_serializing_cache_store(): void
    {
        $row = new stdClass();
        $row->class = 'RuntimeException';
        $row->latest = CarbonImmutable::parse('2026-11-05 12:00:00');

        Cache::store('database')->put('pulse-card', [collect([$row]), 1.5], 60);
        [$rows] = Cache::store('database')->get('pulse-card');

        $this->assertInstanceOf(Collection::class, $rows);
        $this->assertInstanceOf(stdClass::class, $rows->first());
        $this->assertInstanceOf(CarbonImmutable::class, $rows->first()->latest);
        $this->assertSame('RuntimeException', $rows->first()->class);
    }

    public function test_other_classes_are_still_not_unserialized(): void
    {
        Cache::store('database')->put('other', new \ArrayObject([1]), 60);

        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, Cache::store('database')->get('other'));
    }
}
