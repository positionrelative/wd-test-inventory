<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    /** @var list<array{name: string, contact_person: string, email: string, phone: string|null}> */
    private const VENDORS = [
        [
            'name' => 'Northwind IT Solutions',
            'contact_person' => 'Kovács Anna',
            'email' => 'office@nits.hu',
            'phone' => '+36 1 555 0101',
        ],
        [
            'name' => 'Contoso Leasing Kft.',
            'contact_person' => 'Nagy Péter',
            'email' => 'finance@contoso-leasing.hu',
            'phone' => '+36 20 123 4567',
        ],
        [
            'name' => 'MTZ Hardware Zrt.',
            'contact_person' => 'Tóth Eszter',
            'email' => 'office@mtz-hardware.hu',
            'phone' => null,
        ],
    ];

    public function run(): void
    {
        foreach (self::VENDORS as $vendor) {
            Vendor::query()->firstOrCreate(['name' => $vendor['name']], $vendor);
        }
    }
}
