<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Contract;
use App\Models\Manufacturer;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            ManufacturerSeeder::class,
            VendorSeeder::class,
        ]);

        // Factories reuse the seeded manufacturers and vendors instead of creating random ones.
        $manufacturers = Manufacturer::all();
        $vendors = Vendor::all();

        // Contracts with assets on them, plus a few free assets.
        Contract::factory()
            ->count(3)
            ->recycle($vendors)
            ->hasAttached(Asset::factory()->count(3)->recycle($manufacturers), relationship: 'assets')
            ->create();

        Asset::factory()->count(5)->recycle($manufacturers)->create();
    }
}
