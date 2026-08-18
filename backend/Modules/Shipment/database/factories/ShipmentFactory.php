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
        // year/sequence_no/invoice_no are computed here rather than in
        // an afterCreating() callback: `year`/`sequence_no` are NOT NULL
        // columns with no default (see the shipments migration), so the
        // very first insert a factory performs must already carry them
        // — an afterCreating() callback runs strictly after that first
        // insert has already committed (or failed).
        $year = (int) now()->year;
        $sequence = ShipmentInvoiceNumberGenerator::nextFor($year);

        return [
            'order_id' => Order::factory(),
            'created_by' => User::factory(),
            'total_quantity' => fake()->numberBetween(100, 5000),
            'total_cbm' => fake()->randomFloat(3, 1, 50),
            'shipment_date' => fake()->date(),
            'status' => 'draft',
            'year' => $year,
            'sequence_no' => $sequence,
            'invoice_no' => ShipmentInvoiceNumberGenerator::format($year, $sequence),
        ];
    }
}
