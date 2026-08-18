<?php

namespace Modules\Subcontract\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Production\App\Models\PieceSerial;
use Modules\Subcontract\App\Models\SubcontractOrder;

/**
 * PRD v2 §3.24 — Inward Subcontract is job-work capacity we sell to an
 * external party: they bring their own cut fabric, our factory sews it
 * (tagged onto a real CutTicket via `inward_subcontract_order_id`), and
 * App\Services\QcService's inward branch leaves those pieces at
 * `qc_passed` instead of auto-intaking them into our own Finished Goods
 * (they were never ours). `dispatchBack()` is the point where the
 * finished pieces physically leave — reusing PieceSerial's existing
 * `shipped` status rather than inventing a new one, since "shipped out
 * of this factory, not into our own Finished Goods" is exactly what it
 * already means for a StockTransfer-dispatched piece.
 */
class SubcontractInwardService
{
    public static function dispatchBack(SubcontractOrder $order, int $dispatchedBy): SubcontractOrder
    {
        if ($order->direction !== 'inward') {
            throw ValidationException::withMessages([
                'direction' => 'This action only applies to Inward subcontract orders.',
            ]);
        }

        if ($order->status === 'closed') {
            throw ValidationException::withMessages([
                'status' => "Subcontract order {$order->subcontract_no} has already been dispatched back.",
            ]);
        }

        return DB::transaction(function () use ($order, $dispatchedBy) {
            $pieceIds = PieceSerial::whereHas('bundle.cutTicket', fn ($q) => $q->where('inward_subcontract_order_id', $order->id))
                ->where('status', 'qc_passed')
                ->lockForUpdate()
                ->pluck('id');

            if ($pieceIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'status' => 'No QC-passed pieces are ready to dispatch back for this job.',
                ]);
            }

            PieceSerial::whereIn('id', $pieceIds)->update(['status' => 'shipped']);

            $income = $order->valueFor($pieceIds->count());
            SubcontractLedgerService::post($order, 'job_work_income', $income, $dispatchedBy);

            $order->job_work_income_amount = $income;
            $order->dispatched_back_at = now();
            $order->status = 'closed';
            $order->save();

            return $order->fresh();
        });
    }
}
