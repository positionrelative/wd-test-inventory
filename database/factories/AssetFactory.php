<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_tag' => fake()->unique()->bothify('AT-######'),
            'serial_number' => fake()->unique()->bothify('SN-??########'),
            'model_id' => AssetModel::factory(),
        ];
    }
}
