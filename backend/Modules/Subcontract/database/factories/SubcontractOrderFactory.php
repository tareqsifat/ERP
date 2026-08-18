<?php

namespace Modules\Subcontract\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Location\App\Models\Location;
use Modules\Order\App\Models\Order;
use Modules\Party\App\Models\Party;
use Modules\Subcontract\App\Models\SubcontractOrder;
use Modules\Subcontract\App\Services\SubcontractNumberGenerator;

/**
 * @extends Factory<SubcontractOrder>
 */
class SubcontractOrderFactory extends Factory
{
    protected $model = SubcontractOrder::class;

    public function definition(): array
    {
        // year/sequence_no/subcontract_no computed here rather than in an
        // afterCreating() callback — same NOT-NULL-before-first-save()
        // lesson as ShipmentFactory/RawMaterialPurchaseOrderFactory/
        // StockTransferFactory (failed_doc.md Pass 2).
        $year = (int) now()->year;
        $sequence = SubcontractNumberGenerator::nextFor($year);

        return [
            'direction' => 'outward',
            'party_id' => Party::factory()->subcontractor(),
            'order_id' => Order::factory(),
            'style' => 'A1',
            'color' => 'BLK',
            'size' => 'M',
            'rate' => 25,
            'rate_unit' => 'piece',
            'quantity_expected' => 100,
            'location_id' => Location::factory()->ofTypeFactory(),
            'expected_date' => now()->addDays(14)->toDateString(),
            'status' => 'open',
            'created_by' => User::factory(),
            'year' => $year,
            'sequence_no' => $sequence,
            'subcontract_no' => SubcontractNumberGenerator::format($year, $sequence),
        ];
    }

    public function outward(): static
    {
        return $this->state(fn () => ['direction' => 'outward']);
    }

    public function inward(): static
    {
        return $this->state(fn () => [
            'direction' => 'inward',
            'order_id' => null,
        ]);
    }

    public function withRawMaterial(): static
    {
        return $this->state(fn () => [
            'raw_material_id' => \Modules\RawMaterial\App\Models\RawMaterial::factory(),
            'raw_material_quantity' => 50,
        ]);
    }
}
