<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use App\Services\Support\CachedCounter;
use Tests\TestCase;

final class CachedCounterTest extends TestCase
{
    private CachedCounter $counter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->counter = $this->app->make(CachedCounter::class);
    }

    public function test_caches_the_count(): void
    {
        $this->assertSame(1, $this->counter->remember('items', static fn () => 1));
        $this->assertSame(1, $this->counter->remember('items', static fn () => 2));
    }

    public function test_forget_makes_the_next_read_recount(): void
    {
        $this->counter->remember('items', static fn () => 1);
        $this->counter->forget('items');

        $this->assertSame(2, $this->counter->remember('items', static fn () => 2));
    }

    public function test_a_count_computed_before_a_write_is_not_served_after_it(): void
    {
        // A reader computes the old count; a write and its forget() happen
        // before the reader stores the result.
        $this->counter->remember('items', function (): int {
            $stale = 1;
            $this->counter->forget('items');

            return $stale;
        });

        $this->assertSame(2, $this->counter->remember('items', static fn () => 2));
    }

    public function test_keys_are_independent(): void
    {
        $this->counter->remember('a', static fn () => 1);
        $this->counter->remember('b', static fn () => 5);
        $this->counter->forget('a');

        $this->assertSame(5, $this->counter->remember('b', static fn () => 9));
    }
}
