<?php

namespace Modules\RawMaterial\App\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Location\App\Models\Location;
use Modules\RawMaterial\App\Models\RawMaterial;
use Modules\RawMaterial\App\Models\RawMaterialStockMovement;

/**
 * The ONLY code allowed to write to raw_material_stock_movements
 * (sdd.md §5: ledger is the source of truth). Every other module that
 * needs to move raw material stock (Cutting, Subcontract issue, PO
 * receipt) calls through here rather than creating movements directly,
 * so the sign-per-type rule and the "factory/store only" location rule
 * (PRD v2 §3.21) are enforced in exactly one place.
 */
class RawMaterialStockService
{
    public static function receipt(RawMaterial $rawMaterial, Location $location, string $quantity, int $createdBy, ?Model $reference = null, ?string $remarks = null): RawMaterialStockMovement
    {
        return self::record($rawMaterial, $location, 'receipt', $quantity, $createdBy, $reference, $remarks);
    }

    public static function issue(RawMaterial $rawMaterial, Location $location, string $quantity, int $createdBy, ?Model $reference = null, ?string $remarks = null): RawMaterialStockMovement
    {
        // quantity is passed as a positive "amount issued"; stored signed-negative.
        return self::record($rawMaterial, $location, 'issue', bcmul($quantity, '-1', 3), $createdBy, $reference, $remarks);
    }

    public static function adjustment(RawMaterial $rawMaterial, Location $location, string $signedQuantity, int $createdBy, ?string $remarks = null): RawMaterialStockMovement
    {
        return self::record($rawMaterial, $location, 'adjustment', $signedQuantity, $createdBy, null, $remarks);
    }

    private static function record(RawMaterial $rawMaterial, Location $location, string $type, string $signedQuantity, int $createdBy, ?Model $reference, ?string $remarks): RawMaterialStockMovement
    {
        if (! in_array($location->type, ['factory', 'store'], true)) {
            // PRD v2 §3.21: "Raw material is factory/store-scoped only in
            // v1 — showrooms don't hold raw material."
            throw new \InvalidArgumentException('Raw material stock is only tracked at Factory or Store locations.');
        }

        $movement = new RawMaterialStockMovement([
            'raw_material_id' => $rawMaterial->id,
            'location_id' => $location->id,
            'type' => $type,
            'quantity' => $signedQuantity,
            'occurred_on' => now()->toDateString(),
            'created_by' => $createdBy,
            'remarks' => $remarks,
        ]);

        if ($reference) {
            $movement->reference()->associate($reference);
        }

        $movement->save();

        return $movement;
    }
}
