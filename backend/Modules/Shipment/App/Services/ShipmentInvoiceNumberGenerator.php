<?php

namespace Modules\Shipment\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Shipment\App\Models\Shipment;

/**
 * PRD v1 §3.6: invoice format `SHIP-YYYY-NNNN`, NNNN resetting per
 * calendar year. Must be called inside a DB transaction (see
 * ShipmentController@store) — the `lockForUpdate()` row-locks matching
 * rows for the current year so two concurrent requests can't read the
 * same max sequence and both compute the same "next" number.
 */
class ShipmentInvoiceNumberGenerator
{
    public static function nextFor(int $year): int
    {
        $maxSequence = Shipment::withTrashed()
            ->where('year', $year)
            ->lockForUpdate()
            ->max('sequence_no');

        return ($maxSequence ?? 0) + 1;
    }

    public static function format(int $year, int $sequence): string
    {
        return sprintf('SHIP-%d-%04d', $year, $sequence);
    }
}
