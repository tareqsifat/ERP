<?php

namespace Modules\Hrm\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Hrm\App\Models\Employee;
use Modules\Hrm\App\Models\SalaryPayment;

/**
 * @extends Factory<SalaryPayment>
 */
class SalaryPaymentFactory extends Factory
{
    protected $model = SalaryPayment::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'month' => now()->month,
            'year' => now()->year,
            'salary_amount' => 15000,
            'paid_amount' => 0,
            'created_by' => User::factory(),
        ];
    }
}
