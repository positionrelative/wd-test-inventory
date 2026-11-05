<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Models\Manufacturer;
use Database\Seeders\ManufacturerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ManufacturerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_manufacturers_idempotently(): void
    {
        $this->seed(ManufacturerSeeder::class);
        $count = Manufacturer::query()->count();

        $this->seed(ManufacturerSeeder::class);

        $this->assertGreaterThan(0, $count);
        $this->assertSame($count, Manufacturer::query()->count());
        $this->assertDatabaseHas('manufacturers', ['name' => 'Dell']);
    }

    public function test_database_seeder_reuses_seeded_manufacturers(): void
    {
        $this->seed();

        $this->assertSame(
            Manufacturer::query()->count(),
            Manufacturer::query()->whereIn('name', ['Apple', 'Cisco', 'Dell', 'HP', 'Lenovo', 'Logitech', 'Samsung'])->count(),
        );
    }
}
