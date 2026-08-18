<?php

namespace Modules\RawMaterial\App\Services;

use Modules\RawMaterial\App\Models\RawMaterialPurchaseOrder;

/**
 * Same pattern as Modules/Shipment's ShipmentInvoiceNumberGenerator:
 * `PO-YYYY-NNNN`, NNNN resetting per calendar year, computed inside a DB
 * transaction with `lockForUpdate()` to avoid a race between two
 * concurrent PO creations.
 */
class PurchaseOrderNumberGenerator
{
    public static function nextFor(int $year): int
    {
        $maxSequence = RawMaterialPurchaseOrder::withTrashed()
            ->where('year', $year)
            ->lockForUpdate()
            ->max('sequence_no');

        return ($maxSequence ?? 0) + 1;
    }

    public static function format(int $year, int $sequence): string
    {
        return sprintf('PO-%d-%04d', $year, $sequence);
    }
}
