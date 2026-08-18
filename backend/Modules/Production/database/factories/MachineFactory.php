<?php

namespace Modules\Production\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Production\App\Models\Machine;

/**
 * @extends Factory<Machine>
 */
class MachineFactory extends Factory
{
    protected $model = Machine::class;

    public function definition(): array
    {
        return [
            'tag' => 'M-'.fake()->unique()->numerify('####'),
            'type' => fake()->randomElement(['single needle', 'overlock', 'flatlock', 'button hole', 'button attach']),
            'status' => 'active',
        ];
    }
}
