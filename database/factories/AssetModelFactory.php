<?php

namespace Database\Factories;

use App\Models\AssetModel;
use App\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetModel>
 */
class AssetModelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'model_number' => fake()->unique()->bothify('MDL-####-??'),
            'manufacturer_id' => Manufacturer::factory(),
        ];
    }
}
