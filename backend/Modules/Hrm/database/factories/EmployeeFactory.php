<?php

namespace Modules\Hrm\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Hrm\App\Models\Designation;
use Modules\Hrm\App\Models\Employee;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'gender' => fake()->randomElement(['male', 'female']),
            'employment_type' => 'full-time',
            'birth_date' => fake()->date(),
            'joining_date' => now()->toDateString(),
            'designation_id' => Designation::factory(),
            'salary' => 15000,
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }
}
