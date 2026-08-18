<?php

namespace Modules\RawMaterial\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\RawMaterial\App\Models\RawMaterial;

/**
 * @extends Factory<RawMaterial>
 */
class RawMaterialFactory extends Factory
{
    protected $model = RawMaterial::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Cotton Jersey Fabric', 'Poly Thread', 'YKK Zipper', 'Woven Label', 'Poly Bag']),
            'category' => fake()->randomElement(['fabric', 'trim', 'packaging', 'other']),
            'unit' => fake()->randomElement(['kg', 'meter', 'pcs', 'cone', 'roll']),
            'reorder_level' => fake()->randomFloat(3, 10, 200),
            'unit_cost' => fake()->randomFloat(2, 0.5, 20),
            'is_active' => true,
        ];
    }
}
