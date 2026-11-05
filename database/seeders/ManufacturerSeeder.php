<?php

namespace Database\Seeders;

use App\Models\Manufacturer;
use Illuminate\Database\Seeder;

class ManufacturerSeeder extends Seeder
{
    /** @var list<string> */
    private const MANUFACTURERS = [
        'Apple',
        'Cisco',
        'Dell',
        'HP',
        'Lenovo',
        'Logitech',
        'Samsung',
    ];

    public function run(): void
    {
        foreach (self::MANUFACTURERS as $name) {
            Manufacturer::query()->firstOrCreate(['name' => $name]);
        }
    }
}
