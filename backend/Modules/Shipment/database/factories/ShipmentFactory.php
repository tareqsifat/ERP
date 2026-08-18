<?php

namespace Modules\Shipment\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Order\App\Models\Order;
use Modules\Shipment\App\Models\Shipment;
use Modules\Shipment\App\Services\ShipmentInvoiceNumberGenerator;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'created_by' => User::factory(),
            'total_quantity' => fake()->numberBetween(100, 5000),
            'total_cbm' => fake()->randomFloat(3, 1, 50),
            'shipment_date' => fake()->date(),
            'status' => 'draft',
        ];
    }

    /**
     * Mirrors OrderFactory's pattern: invoice_no/year/sequence_no are
     * normally only set post-insert by ShipmentInvoiceNumberGenerator
     * (see ShipmentController), so tests that need a real invoice_no use
     * this state explicitly.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Shipment $shipment) {
            $year = (int) now()->year;
            $sequence = ShipmentInvoiceNumberGenerator::nextFor($year);
            $shipment->year = $year;
            $shipment->sequence_no = $sequence;
            $shipment->invoice_no = ShipmentInvoiceNumberGenerator::format($year, $sequence);
            $shipment->saveQuietly();
        });
    }
}
