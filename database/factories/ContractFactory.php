<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'title' => fake()->sentence(3),
            'contract_number' => fake()->unique()->bothify('CN-####-####'),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween($start, '+3 years')->format('Y-m-d'),
            'vendor_id' => Vendor::factory(),
        ];
    }
}
