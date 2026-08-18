<?php

namespace Modules\Subcontract\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Production\App\Models\CutTicket;
use Modules\Production\App\Models\PieceSerial;
use Modules\Production\App\Services\CuttingService;
use Modules\Subcontract\App\Models\SubcontractOrder;
use Modules\Subcontract\App\Models\SubcontractOrderPiece;

/**
 * PRD v2 §3.23 — Outward Subcontract issues work to an external party in
 * one of two shapes:
 *
 *  - `issuePieces()`: already-cut PieceSerials are handed over by
 *    reference. Deliberately does NOT mutate PieceSerial.status — the
 *    piece is still "ours", just physically elsewhere; SubcontractOrderPiece
 *    is the sole source of truth for "is this piece currently with a
 *    subcontractor" (see that model's docblock).
 *  - `issueRawMaterial()`: raw material is issued instead of pre-cut
 *    pieces. This creates and finalizes a *real* CutTicket (reusing
 *    App\Services\CuttingService — same fabric-deduction + bundle/serial
 *    generation as an in-house cut) so subcontractor-cut pieces keep full
 *    traceability, then attaches every generated piece to this order.
 */
class SubcontractOutwardService
{
    public static function issuePieces(SubcontractOrder $order, array $pieceSerialIds, int $issuedBy): array
    {
        self::guardOutward($order);

        if (empty($pieceSerialIds)) {
            throw ValidationException::withMessages([
                'piece_serial_ids' => 'At least one piece serial is required.',
            ]);
        }

        return DB::transaction(function () use ($order, $pieceSerialIds, $issuedBy) {
            $pieces = PieceSerial::whereIn('id', $pieceSerialIds)->get();

            if ($pieces->count() !== count(array_unique($pieceSerialIds))) {
                throw ValidationException::withMessages([
                    'piece_serial_ids' => 'One or more piece serials were not found.',
                ]);
            }

            foreach ($pieces as $piece) {
                if (SubcontractOrderPiece::where('piece_serial_id', $piece->id)->outstanding()->exists()) {
                    throw ValidationException::withMessages([
                        'piece_serial_ids' => "Piece {$piece->serial} is already issued to an open subcontract order.",
                    ]);
                }

                SubcontractOrderPiece::create([
                    'subcontract_order_id' => $order->id,
                    'piece_serial_id' => $piece->id,
                    'issued_at' => now(),
                ]);
            }

            SubcontractLedgerService::post($order, 'issue_value', $order->valueFor($pieces->count()), $issuedBy);

            return $pieces->all();
        });
    }

    public static function issueRawMaterial(SubcontractOrder $order, string $cutDate, int $cuttingMasterId, int $bundleSize, int $quantity, int $issuedBy): CutTicket
    {
        self::guardOutward($order);

        if (! $order->raw_material_id || ! $order->raw_material_quantity) {
            throw ValidationException::withMessages([
                'raw_material_id' => 'This subcontract order has no raw material configured to issue.',
            ]);
        }

        if (! $order->order_id) {
            // Guaranteed by StoreSubcontractOrderRequest for direction=outward,
            // re-checked here since this service could theoretically be
            // called against a stale/hand-edited row.
            throw ValidationException::withMessages([
                'order_id' => 'This subcontract order has no linked Order to hang a Cut Ticket off of.',
            ]);
        }

        if (! $order->location_id) {
            throw ValidationException::withMessages([
                'location_id' => 'This subcontract order has no location configured to issue raw material from.',
            ]);
        }

        return DB::transaction(function () use ($order, $cutDate, $cuttingMasterId, $bundleSize, $quantity, $issuedBy) {
            $cutTicket = new CutTicket([
                'order_id' => $order->order_id,
                'style' => $order->style,
                'color' => $order->color,
                'size' => $order->size,
                'cut_date' => $cutDate,
                'cutting_master_id' => $cuttingMasterId,
                'raw_material_id' => $order->raw_material_id,
                'fabric_consumed' => $order->raw_material_quantity,
                'location_id' => $order->location_id,
                'bundle_size' => $bundleSize,
                'planned_quantity' => $quantity,
            ]);
            $cutTicket->status = 'draft';
            $cutTicket->save();

            CuttingService::finalize($cutTicket, $issuedBy);

            $pieceIds = PieceSerial::whereHas('bundle', fn ($q) => $q->where('cut_ticket_id', $cutTicket->id))
                ->pluck('id');

            foreach ($pieceIds as $pieceId) {
                SubcontractOrderPiece::create([
                    'subcontract_order_id' => $order->id,
                    'piece_serial_id' => $pieceId,
                    'issued_at' => now(),
                ]);
            }

            SubcontractLedgerService::post($order, 'issue_value', $order->valueFor($pieceIds->count()), $issuedBy);

            return $cutTicket->load('bundles.pieceSerials');
        });
    }

    /**
     * @param  int[]  $returnedPieceSerialIds  came back from the subcontractor and are QC-ready
     * @param  int[]  $writtenOffPieceSerialIds  lost/damaged in the subcontractor's hands, never coming back
     */
    public static function returnPieces(SubcontractOrder $order, array $returnedPieceSerialIds, array $writtenOffPieceSerialIds, int $returnedBy): SubcontractOrder
    {
        self::guardOutward($order);

        return DB::transaction(function () use ($order, $returnedPieceSerialIds, $writtenOffPieceSerialIds, $returnedBy) {
            $returnedCount = self::resolve($order, $returnedPieceSerialIds, 'returned');
            $writtenOffCount = self::resolve($order, $writtenOffPieceSerialIds, 'written_off');

            if ($returnedCount === 0 && $writtenOffCount === 0) {
                throw ValidationException::withMessages([
                    'returned_piece_serial_ids' => 'At least one returned or written-off piece is required.',
                ]);
            }

            if ($returnedCount > 0) {
                PieceSerial::whereIn('id', $returnedPieceSerialIds)->update(['status' => 'sewn']);
                SubcontractLedgerService::post($order, 'return_value', $order->valueFor($returnedCount), $returnedBy);
            }

            if ($writtenOffCount > 0) {
                SubcontractLedgerService::post($order, 'shortage_deduction', $order->valueFor($writtenOffCount), $returnedBy);
            }

            self::refreshStatus($order);

            return $order->fresh();
        });
    }

    public static function refreshStatus(SubcontractOrder $order): void
    {
        $totalIssued = SubcontractOrderPiece::where('subcontract_order_id', $order->id)->count();
        if ($totalIssued === 0) {
            return;
        }

        $outstanding = SubcontractOrderPiece::where('subcontract_order_id', $order->id)->outstanding()->count();

        $order->status = match (true) {
            $outstanding === 0 => 'closed',
            $outstanding < $totalIssued => 'partially_returned',
            default => 'open',
        };
        $order->save();
    }

    /**
     * @return int  how many rows were actually resolved (0 if $pieceSerialIds is empty)
     */
    private static function resolve(SubcontractOrder $order, array $pieceSerialIds, string $resolution): int
    {
        if (empty($pieceSerialIds)) {
            return 0;
        }

        $rows = SubcontractOrderPiece::where('subcontract_order_id', $order->id)
            ->whereIn('piece_serial_id', $pieceSerialIds)
            ->outstanding()
            ->get();

        if ($rows->count() !== count(array_unique($pieceSerialIds))) {
            throw ValidationException::withMessages([
                'piece_serial_ids' => 'One or more pieces are not currently outstanding on this subcontract order.',
            ]);
        }

        SubcontractOrderPiece::whereIn('id', $rows->pluck('id'))->update([
            'resolution' => $resolution,
            'resolved_at' => now(),
        ]);

        return $rows->count();
    }

    private static function guardOutward(SubcontractOrder $order): void
    {
        if ($order->direction !== 'outward') {
            throw ValidationException::withMessages([
                'direction' => 'This action only applies to Outward subcontract orders.',
            ]);
        }
    }
}
