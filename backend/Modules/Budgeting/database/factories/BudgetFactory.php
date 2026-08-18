<?php

namespace Modules\Budgeting\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Budgeting\App\Models\Budget;
use Modules\Order\App\Models\Order;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(100, 5000);
        $unitPrice = fake()->randomFloat(2, 1, 20);

        return [
            'order_id' => Order::factory(),
            'style' => 'ST-'.fake()->numberBetween(1, 20),
            'budgeted_quantity' => $quantity,
            'average_unit_price' => $unitPrice,
            'total_value' => round($quantity * $unitPrice, 2),
            'status' => 'draft',
        ];
    }
}
