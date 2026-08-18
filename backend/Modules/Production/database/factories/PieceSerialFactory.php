<?php

namespace Modules\Production\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Order\App\Models\Order;
use Modules\Production\App\Models\Bundle;
use Modules\Production\App\Models\PieceSerial;

/**
 * @extends Factory<PieceSerial>
 *
 * Same rationale as BundleFactory — test-only shortcut for exercising
 * SewingService/QcService without a full CuttingService::finalize()
 * run. Production code never creates a PieceSerial via this factory.
 */
class PieceSerialFactory extends Factory
{
    protected $model = PieceSerial::class;

    public function definition(): array
    {
        return [
            'bundle_id' => Bundle::factory(),
            'order_id' => Order::factory(),
            'serial' => fake()->unique()->bothify('SN-########-###'),
            'status' => 'cut',
        ];
    }
}
