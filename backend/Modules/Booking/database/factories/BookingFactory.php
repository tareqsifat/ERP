<?php

namespace Modules\Booking\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Booking\App\Models\Booking;
use Modules\Order\App\Models\Order;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'preparer_id' => User::factory(),
            'booking_date' => fake()->date(),
            'composition' => '100% Cotton',
            'process_loss_percent' => fake()->randomFloat(2, 1, 10),
            'status' => 'draft',
        ];
    }
}
