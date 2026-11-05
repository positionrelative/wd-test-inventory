<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Models\Contract;
use App\Models\Vendor;
use Database\Seeders\VendorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VendorSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_vendors_idempotently(): void
    {
        $this->seed(VendorSeeder::class);
        $count = Vendor::query()->count();

        $this->seed(VendorSeeder::class);

        $this->assertGreaterThan(0, $count);
        $this->assertSame($count, Vendor::query()->count());
        $this->assertDatabaseHas('vendors', ['name' => 'Northwind IT Solutions']);
    }

    public function test_database_seeder_reuses_seeded_vendors(): void
    {
        $this->seed(VendorSeeder::class);
        $seeded = Vendor::query()->pluck('id');

        $this->seed();

        $this->assertSame($seeded->count(), Vendor::query()->count());
        $this->assertSame(0, Contract::query()->whereNotIn('vendor_id', $seeded)->count());
    }
}
