<?php

namespace Modules\RawMaterial\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Location\App\Models\Location;
use Modules\Party\App\Models\Party;
use Modules\RawMaterial\App\Models\RawMaterialPurchaseOrder;
use Modules\RawMaterial\App\Services\PurchaseOrderNumberGenerator;

/**
 * @extends Factory<RawMaterialPurchaseOrder>
 */
class RawMaterialPurchaseOrderFactory extends Factory
{
    protected $model = RawMaterialPurchaseOrder::class;

    public function definition(): array
    {
        // year/sequence_no/po_no are computed here rather than in an
        // afterCreating() callback: `year`/`sequence_no` are NOT NULL
        // columns with no default (see the
        // raw_material_purchase_orders migration), so the very first
        // insert a factory performs must already carry them — an
        // afterCreating() callback runs strictly after that first
        // insert has already committed (or failed).
        $year = (int) now()->year;
        $sequence = PurchaseOrderNumberGenerator::nextFor($year);

        return [
            'supplier_id' => Party::factory()->supplier(),
            'location_id' => Location::factory()->ofTypeStore(),
            'status' => 'draft',
            'order_date' => fake()->date(),
            'created_by' => User::factory(),
            'year' => $year,
            'sequence_no' => $sequence,
            'po_no' => PurchaseOrderNumberGenerator::format($year, $sequence),
        ];
    }
}
