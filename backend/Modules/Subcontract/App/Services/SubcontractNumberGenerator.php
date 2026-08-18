<?php

namespace Modules\Subcontract\App\Services;

use Modules\Subcontract\App\Models\SubcontractOrder;

/**
 * Same pattern as Shipment/RawMaterial/StockTransfer's number generators:
 * `SC-YYYY-NNNN`, NNNN resetting per calendar year, computed inside a DB
 * transaction with `lockForUpdate()` to avoid a race between two
 * concurrent creates. One sequence shared by both outward and inward
 * orders — direction is not part of the number.
 */
class SubcontractNumberGenerator
{
    public static function nextFor(int $year): int
    {
        $maxSequence = SubcontractOrder::withTrashed()
            ->where('year', $year)
            ->lockForUpdate()
            ->max('sequence_no');

        return ($maxSequence ?? 0) + 1;
    }

    public static function format(int $year, int $sequence): string
    {
        return sprintf('SC-%d-%04d', $year, $sequence);
    }
}
