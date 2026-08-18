<?php

namespace Modules\Hrm\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Hrm\App\Models\Designation;

/**
 * @extends Factory<Designation>
 */
class DesignationFactory extends Factory
{
    protected $model = Designation::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'description' => null,
            'is_active' => true,
        ];
    }
}
