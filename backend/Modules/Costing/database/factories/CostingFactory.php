<?php

namespace Modules\Costing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Costing\App\Models\Costing;
use Modules\Order\App\Models\Order;

/**
 * @extends Factory<Costing>
 */
class CostingFactory extends Factory
{
    protected $model = Costing::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(100, 5000);
        $unitCost = fake()->randomFloat(2, 1, 15);

        return [
            'order_id' => Order::factory(),
            'style' => 'ST-'.fake()->numberBetween(1, 20),
            'costed_quantity' => $quantity,
            'average_unit_cost' => $unitCost,
            'total_cost' => round($quantity * $unitCost, 2),
            'status' => 'draft',
        ];
    }
}
