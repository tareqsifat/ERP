<?php

namespace Modules\Subcontract\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Production\App\Models\PieceSerial;
use Modules\Subcontract\App\Models\SubcontractOrder;
use Modules\Subcontract\App\Models\SubcontractOrderPiece;

/**
 * @extends Factory<SubcontractOrderPiece>
 */
class SubcontractOrderPieceFactory extends Factory
{
    protected $model = SubcontractOrderPiece::class;

    public function definition(): array
    {
        return [
            'subcontract_order_id' => SubcontractOrder::factory(),
            'piece_serial_id' => PieceSerial::factory(),
            'issued_at' => now(),
        ];
    }
}
