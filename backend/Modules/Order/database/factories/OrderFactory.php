<?php

namespace Modules\Order\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Order\App\Models\Order;
use Modules\Order\App\Services\OrderNumberGenerator;
use Modules\Party\App\Models\Party;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'party_id' => Party::factory()->buyer(),
            'merchandiser_id' => User::factory(),
            'title' => fake()->words(3, true),
            'fabrication' => fake()->randomElement(['100% Cotton', 'Cotton/Poly', 'Fleece']),
            'gsm' => (string) fake()->numberBetween(140, 320),
            'shipment_mode' => fake()->randomElement(['sea', 'air', 'sea_air', 'road', 'courier']),
            'payment_mode' => fake()->randomElement(['lc', 'tt', 'advance', 'on_delivery']),
            'year' => (int) date('Y'),
            'season' => fake()->randomElement(['Spring/Summer', 'Autumn/Winter']),
            'status' => 'pending',
        ];
    }

    /**
     * factory()->create() would leave order_no null since it's normally
     * only set post-insert by OrderNumberGenerator (see OrderController).
     * Tests that need a real order_no should use this state explicitly.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Order $order) {
            $order->order_no = OrderNumberGenerator::generateFor($order);
            $order->saveQuietly();
        });
    }
}
