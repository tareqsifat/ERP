<?php

namespace Modules\Sampling\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Order\App\Models\Order;
use Modules\Sampling\App\Models\Sample;

/**
 * @extends Factory<Sample>
 */
class SampleFactory extends Factory
{
    protected $model = Sample::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'consignee' => fake()->company(),
            'style_number' => 'ST-'.fake()->numberBetween(1, 20),
            'item' => fake()->randomElement(['T-Shirt', 'Polo', 'Hoodie', 'Trouser']),
            'sample_type' => fake()->randomElement(['proto', 'fit', 'pp', 'size_set', 'shipment', 'salesman']),
            'quantity' => fake()->numberBetween(1, 10),
            'status' => 'requested',
        ];
    }
}
