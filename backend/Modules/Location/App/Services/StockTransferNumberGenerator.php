<?php

namespace Modules\Location\App\Services;

use Modules\Location\App\Models\StockTransfer;

/**
 * Same pattern as RawMaterial's PurchaseOrderNumberGenerator / Shipment's
 * invoice number generator: `ST-YYYY-NNNN`, NNNN resetting per calendar
 * year, computed inside a DB transaction with `lockForUpdate()` to avoid
 * a race between two concurrent dispatches.
 */
class StockTransferNumberGenerator
{
    public static function nextFor(int $year): int
    {
        $maxSequence = StockTransfer::withTrashed()
            ->where('year', $year)
            ->lockForUpdate()
            ->max('sequence_no');

        return ($maxSequence ?? 0) + 1;
    }

    public static function format(int $year, int $sequence): string
    {
        return sprintf('ST-%d-%04d', $year, $sequence);
    }
}
