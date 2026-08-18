<?php

namespace Modules\FinishedGoods\App\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\FinishedGoods\App\Models\FinishedGoodsMovement;
use Modules\Location\App\Models\Location;
use Modules\Order\App\Models\Order;
use Modules\Production\App\Models\PieceSerial;

/**
 * The ONLY code allowed to write to finished_goods_movements — mirrors
 * Modules\RawMaterial\App\Services\RawMaterialStockService's role for
 * the raw material ledger. Called by:
 * - Modules\Production\App\Services\QcService (intake on QC pass)
 * - Modules\Location\App\Services\StockTransferService (transfer out/in)
 * - Modules\Shipment (deducts on ship — Phase 4 wiring; see
 *   Modules/Shipment/README.md for the Phase-4-added note)
 */
class FinishedGoodsStockService
{
    public static function intakeFromQc(PieceSerial $piece, Location $location, int $createdBy): FinishedGoodsMovement
    {
        $order = $piece->order;
        $cutTicket = $piece->bundle->cutTicket;

        return self::record(
            $location, $order, $cutTicket->style, $cutTicket->color, $cutTicket->size,
            1, 'qc_intake', $createdBy, $piece, null,
        );
    }

    public static function transferOut(Location $from, Order $order, string $style, string $color, ?string $size, int $quantity, int $createdBy, ?Model $reference = null): FinishedGoodsMovement
    {
        return self::record($from, $order, $style, $color, $size, -$quantity, 'transfer_out', $createdBy, null, $reference);
    }

    public static function transferIn(Location $to, Order $order, string $style, string $color, ?string $size, int $quantity, int $createdBy, ?Model $reference = null): FinishedGoodsMovement
    {
        return self::record($to, $order, $style, $color, $size, $quantity, 'transfer_in', $createdBy, null, $reference);
    }

    public static function shipment(Location $from, Order $order, string $style, string $color, ?string $size, int $quantity, int $createdBy, ?Model $reference = null): FinishedGoodsMovement
    {
        return self::record($from, $order, $style, $color, $size, -$quantity, 'shipment', $createdBy, null, $reference);
    }

    /**
     * sdd.md §5: computed from the ledger. Any combination of filters may
     * be omitted to aggregate more broadly (e.g. omit size for a
     * style/color-level total).
     */
    public static function stockOf(Location $location, Order $order, ?string $style = null, ?string $color = null, ?string $size = null): int
    {
        $query = FinishedGoodsMovement::query()
            ->where('location_id', $location->id)
            ->where('order_id', $order->id);

        if ($style !== null) {
            $query->where('style', $style);
        }
        if ($color !== null) {
            $query->where('color', $color);
        }
        if ($size !== null) {
            $query->where('size', $size);
        }

        return (int) $query->sum('quantity');
    }

    private static function record(Location $location, Order $order, string $style, string $color, ?string $size, int $signedQuantity, string $type, int $createdBy, ?PieceSerial $piece, ?Model $reference): FinishedGoodsMovement
    {
        $movement = new FinishedGoodsMovement([
            'location_id' => $location->id,
            'order_id' => $order->id,
            'style' => $style,
            'color' => $color,
            'size' => $size,
            'piece_serial_id' => $piece?->id,
            'quantity' => $signedQuantity,
            'type' => $type,
            'occurred_on' => now()->toDateString(),
            'created_by' => $createdBy,
        ]);

        if ($reference) {
            $movement->reference()->associate($reference);
        }

        $movement->save();

        return $movement;
    }
}
