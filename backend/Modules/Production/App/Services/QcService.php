<?php

namespace Modules\Production\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\FinishedGoods\App\Services\FinishedGoodsStockService;
use Modules\Location\App\Models\Location;
use Modules\Production\App\Models\PieceSerial;

/**
 * PRD v2 §3.18 — QC operates per piece (not per bundle, since pieces
 * within one bundle can diverge: some pass, some reject — see
 * Modules/Production/README.md "Bundle status vs. piece status").
 *
 * "QC-passed pieces are received into Finished Goods Inventory at the
 * Main Store location, closing the traceability loop from cut piece to
 * finished unit" — this is the exact moment PieceSerial::status becomes
 * `finished_goods` and a `qc_intake` movement is posted via
 * FinishedGoodsStockService, which is the only code allowed to write to
 * finished_goods_movements.
 */
class QcService
{
    public static function pass(PieceSerial $piece, Location $intakeLocation, int $qcByUserId): PieceSerial
    {
        self::guardNotAlreadyQcd($piece);

        return DB::transaction(function () use ($piece, $intakeLocation, $qcByUserId) {
            $piece->status = 'qc_passed';
            $piece->qc_by = $qcByUserId;
            $piece->qc_at = now();
            $piece->qc_reject_reason = null;
            $piece->save();

            FinishedGoodsStockService::intakeFromQc($piece, $intakeLocation, $qcByUserId);

            // Closes the loop: the piece now lives in Finished Goods, not
            // on the floor — see PRD v2 §3.18/§3.20.
            $piece->status = 'finished_goods';
            $piece->save();

            return $piece;
        });
    }

    public static function reject(PieceSerial $piece, string $reason, int $qcByUserId): PieceSerial
    {
        self::guardNotAlreadyQcd($piece);

        $piece->status = 'qc_rejected';
        $piece->qc_reject_reason = $reason;
        $piece->qc_by = $qcByUserId;
        $piece->qc_at = now();
        $piece->save();

        return $piece;
    }

    private static function guardNotAlreadyQcd(PieceSerial $piece): void
    {
        if (in_array($piece->status, ['qc_passed', 'qc_rejected', 'finished_goods', 'shipped'], true)) {
            // Idempotency guard mirroring CuttingService::finalize()'s —
            // re-QCing a piece would double-post a Finished Goods
            // movement on a repeated "pass" call.
            throw ValidationException::withMessages([
                'status' => "Piece {$piece->serial} has already been QC'd (status: {$piece->status}).",
            ]);
        }
    }
}
