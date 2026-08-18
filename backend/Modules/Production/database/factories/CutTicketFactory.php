<?php

namespace Modules\Production\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Location\App\Models\Location;
use Modules\Order\App\Models\Order;
use Modules\Production\App\Models\CutTicket;
use Modules\RawMaterial\App\Models\RawMaterial;

/**
 * @extends Factory<CutTicket>
 */
class CutTicketFactory extends Factory
{
    protected $model = CutTicket::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'booking_id' => null,
            'style' => fake()->randomElement(['A1', 'B2', 'C3']),
            'color' => fake()->randomElement(['BLK', 'WHT', 'NVY']),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'cut_date' => fake()->date(),
            'cutting_master_id' => User::factory(),
            'raw_material_id' => RawMaterial::factory(),
            'fabric_consumed' => fake()->randomFloat(3, 5, 50),
            'location_id' => Location::factory()->ofTypeFactory(),
            'bundle_size' => 20,
            'planned_quantity' => fake()->numberBetween(20, 100),
            'status' => 'draft',
        ];
    }
}
