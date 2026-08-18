<?php

namespace Modules\Production\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Production\App\Models\Line;

/**
 * @extends Factory<Line>
 */
class LineFactory extends Factory
{
    protected $model = Line::class;

    public function definition(): array
    {
        return [
            'name' => 'Line '.fake()->unique()->numberBetween(1, 20),
            'capacity' => fake()->numberBetween(20, 60),
            'is_active' => true,
        ];
    }
}
