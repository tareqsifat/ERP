<?php

namespace Modules\Production\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Production\App\Models\Bundle;
use Modules\Production\App\Models\CutTicket;
use Modules\Production\App\Models\PieceSerial;
use Modules\RawMaterial\App\Services\RawMaterialStockService;

/**
 * PRD v2 §3.17: finalizing a Cut Ticket (1) deducts fabric from Raw
 * Material stock, (2) generates Bundles (configurable bundle size), and
 * (3) generates a unique Piece Serial per piece, pattern
 * `{OrderNo}-{Style}-{Color}-{CutDate:YYMMDD}-{BundleSeq}-{PieceSeq}`.
 *
 * This is the traceability spine — see sdd.md §5 and todo.md Phase 4's
 * "single most important test in the whole project" note. Everything
 * here runs inside one DB transaction: a Cut Ticket is either fully
 * finalized (stock deducted + every bundle + every serial exists) or
 * not finalized at all, never half-done.
 */
class CuttingService
{
    public static function finalize(CutTicket $cutTicket, int $actingUserId): CutTicket
    {
        if ($cutTicket->status === 'finalized') {
            // Idempotency guard: finalizing twice would double-deduct
            // stock and generate duplicate bundles/serials.
            throw ValidationException::withMessages([
                'status' => 'This cut ticket has already been finalized.',
            ]);
        }

        return DB::transaction(function () use ($cutTicket, $actingUserId) {
            RawMaterialStockService::issue(
                $cutTicket->rawMaterial,
                $cutTicket->location,
                (string) $cutTicket->fabric_consumed,
                $actingUserId,
                $cutTicket,
            );

            $orderNo = $cutTicket->order->order_no;
            $cutDateCode = $cutTicket->cut_date->format('ymd');
            $remaining = $cutTicket->planned_quantity;
            $bundleSeq = 0;

            while ($remaining > 0) {
                $bundleSeq++;
                $bundleQuantity = min($remaining, $cutTicket->bundle_size);
                $remaining -= $bundleQuantity;

                $bundle = new Bundle([
                    'cut_ticket_id' => $cutTicket->id,
                    'bundle_no' => str_pad((string) $bundleSeq, 3, '0', STR_PAD_LEFT),
                    'quantity' => $bundleQuantity,
                    'status' => 'cut',
                ]);
                $bundle->save();

                for ($pieceSeq = 1; $pieceSeq <= $bundleQuantity; $pieceSeq++) {
                    $serial = sprintf(
                        '%s-%s-%s-%s-%s-%s',
                        $orderNo,
                        $cutTicket->style,
                        $cutTicket->color,
                        $cutDateCode,
                        $bundle->bundle_no,
                        str_pad((string) $pieceSeq, 3, '0', STR_PAD_LEFT),
                    );

                    $piece = new PieceSerial([
                        'bundle_id' => $bundle->id,
                        'order_id' => $cutTicket->order_id,
                        'serial' => $serial,
                        'status' => 'cut',
                    ]);
                    $piece->save();
                }
            }

            $cutTicket->status = 'finalized';
            $cutTicket->finalized_at = now();
            $cutTicket->save();

            return $cutTicket;
        });
    }
}
