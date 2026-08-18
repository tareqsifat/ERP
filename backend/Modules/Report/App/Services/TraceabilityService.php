<?php

namespace Modules\Report\App\Services;

use Modules\FinishedGoods\App\Models\FinishedGoodsMovement;
use Modules\Production\App\Models\PieceSerial;

/**
 * PRD v2 §3.17/§3.18/§4.2 — "Piece Traceability Lookup (/production/trace/
 * {serial}) — search any serial, see full history." The write-side lives
 * entirely in Modules/Production (CuttingService mints the serial,
 * SewingService/QcService mutate it); Modules/Production's
 * PieceSerialController::show() already exposes the bare row (its
 * docblock explicitly earmarks this fuller read for "a dedicated
 * Report-module endpoint (Phase 7)"). This walks the full chain: Order
 * → Cut Ticket (+ inward Subcontract Order, if this piece was job work
 * coming back in) → Bundle → Piece Serial → every Finished Goods
 * Movement keyed to this exact piece (QC intake, transfer, shipment).
 */
class TraceabilityService
{
    public static function traceBySerial(string $serial): ?array
    {
        $piece = PieceSerial::query()
            ->with(['bundle.cutTicket.inwardSubcontractOrder', 'bundle.line', 'order', 'qcBy'])
            ->where('serial', $serial)
            ->first();

        if (! $piece) {
            return null;
        }

        $cutTicket = $piece->bundle?->cutTicket;

        $movements = FinishedGoodsMovement::query()
            ->where('piece_serial_id', $piece->id)
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->get(['id', 'location_id', 'type', 'quantity', 'occurred_on', 'reference_type', 'reference_id']);

        return [
            'serial' => $piece->serial,
            'status' => $piece->status,
            'order' => $piece->order ? ['id' => $piece->order->id, 'order_no' => $piece->order->order_no ?? null] : null,
            'cut_ticket' => $cutTicket ? [
                'id' => $cutTicket->id,
                'style' => $cutTicket->style,
                'color' => $cutTicket->color,
                'size' => $cutTicket->size,
                'cut_date' => $cutTicket->cut_date,
                'inward_subcontract_order_id' => $cutTicket->inward_subcontract_order_id,
            ] : null,
            'bundle' => $piece->bundle ? [
                'id' => $piece->bundle->id,
                'bundle_no' => $piece->bundle->bundle_no,
                'line' => $piece->bundle->line?->name,
                'assigned_to_line_at' => $piece->bundle->assigned_to_line_at,
                'line_output_at' => $piece->bundle->line_output_at,
            ] : null,
            'qc' => [
                'result' => $piece->status,
                'reject_reason' => $piece->qc_reject_reason,
                'by' => $piece->qcBy?->name,
                'at' => $piece->qc_at,
            ],
            'finished_goods_movements' => $movements,
            'created_at' => $piece->created_at,
        ];
    }
}
