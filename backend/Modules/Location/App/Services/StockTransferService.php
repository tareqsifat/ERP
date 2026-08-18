<?php

namespace Modules\Location\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\FinishedGoods\App\Services\FinishedGoodsStockService;
use Modules\Location\App\Models\Location;
use Modules\Location\App\Models\StockTransfer;
use Modules\Order\App\Models\Order;

/**
 * PRD v2 §3.21 — dispatch deducts Finished Goods stock at the source
 * location immediately (via FinishedGoodsStockService::transferOut());
 * receive adds it at the destination (transferIn()) using the actually
 * *received* quantity, not the dispatched one — a short/over receipt is
 * recorded as `status = discrepancy` rather than silently reconciled,
 * so it surfaces for someone to investigate instead of quietly
 * absorbing shrinkage into the ledger.
 */
class StockTransferService
{
    public static function dispatch(Location $from, Location $to, Order $order, string $style, string $color, ?string $size, int $quantity, int $dispatchedBy): StockTransfer
    {
        if ($from->is($to)) {
            throw ValidationException::withMessages([
                'to_location_id' => 'Source and destination location must be different.',
            ]);
        }

        $available = FinishedGoodsStockService::stockOf($from, $order, $style, $color, $size);
        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$available} units of {$style}/{$color} available at {$from->name} — cannot dispatch {$quantity}.",
            ]);
        }

        return DB::transaction(function () use ($from, $to, $order, $style, $color, $size, $quantity, $dispatchedBy) {
            $year = (int) now()->year;

            // Sequence must be resolved and assigned BEFORE the first
            // save() — `year`/`sequence_no` are NOT NULL columns with no
            // default (see the stock_transfers migration), so saving
            // the transfer before they're set would fail the insert
            // outright under strict SQL mode. One INSERT with every NOT
            // NULL column already populated, not two (same fix applied
            // to Shipment/RawMaterialPurchaseOrder's identical pattern).
            $sequence = StockTransferNumberGenerator::nextFor($year);

            $transfer = new StockTransfer([
                'from_location_id' => $from->id,
                'to_location_id' => $to->id,
                'order_id' => $order->id,
                'style' => $style,
                'color' => $color,
                'size' => $size,
                'quantity_dispatched' => $quantity,
                'dispatched_by' => $dispatchedBy,
                'dispatched_at' => now(),
            ]);
            $transfer->status = 'dispatched';
            $transfer->year = $year;
            $transfer->sequence_no = $sequence;
            $transfer->transfer_no = StockTransferNumberGenerator::format($year, $sequence);
            $transfer->save();

            FinishedGoodsStockService::transferOut($from, $order, $style, $color, $size, $quantity, $dispatchedBy, $transfer);

            return $transfer;
        });
    }

    public static function receive(StockTransfer $transfer, int $quantityReceived, int $receivedBy): StockTransfer
    {
        if ($transfer->status !== 'dispatched') {
            // Idempotency guard — same rationale as CuttingService::finalize():
            // receiving twice would double-post a transfer_in movement.
            throw ValidationException::withMessages([
                'status' => "Transfer {$transfer->transfer_no} has already been received.",
            ]);
        }

        return DB::transaction(function () use ($transfer, $quantityReceived, $receivedBy) {
            $transfer->quantity_received = $quantityReceived;
            $transfer->received_by = $receivedBy;
            $transfer->received_at = now();
            $transfer->status = $quantityReceived === $transfer->quantity_dispatched ? 'received' : 'discrepancy';
            $transfer->save();

            FinishedGoodsStockService::transferIn(
                $transfer->toLocation,
                $transfer->order,
                $transfer->style,
                $transfer->color,
                $transfer->size,
                $quantityReceived,
                $receivedBy,
                $transfer,
            );

            return $transfer;
        });
    }
}
