<?php

namespace Modules\Location\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Location\App\Models\Location;
use Modules\Location\App\Models\StockTransfer;
use Modules\Location\App\Services\StockTransferNumberGenerator;
use Modules\Order\App\Models\Order;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    public function definition(): array
    {
        // year/sequence_no/transfer_no are computed here rather than in
        // an afterCreating() callback: `year`/`sequence_no` are NOT NULL
        // columns with no default (see the stock_transfers migration),
        // so the very first insert a factory performs must already
        // carry them — an afterCreating() callback runs strictly after
        // that first insert has already committed (or failed). Same fix
        // applied to Shipment/RawMaterialPurchaseOrder's identical
        // pattern.
        $year = (int) now()->year;
        $sequence = StockTransferNumberGenerator::nextFor($year);

        return [
            'from_location_id' => Location::factory()->ofTypeStore(),
            'to_location_id' => Location::factory()->ofTypeShowroom(),
            'order_id' => Order::factory(),
            'style' => 'A1',
            'color' => 'BLK',
            'size' => 'M',
            'quantity_dispatched' => 10,
            'dispatched_by' => User::factory(),
            'dispatched_at' => now(),
            'status' => 'dispatched',
            'year' => $year,
            'sequence_no' => $sequence,
            'transfer_no' => StockTransferNumberGenerator::format($year, $sequence),
        ];
    }
}
