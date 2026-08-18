<?php

namespace Modules\Report\App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Accounting\App\Http\Controllers\CashbookController;
use Modules\Accounting\App\Services\PartyFinancialsService;
use Modules\FinishedGoods\App\Models\FinishedGoodsMovement;
use Modules\Order\App\Models\Order;
use Modules\Party\App\Models\Party;
use Modules\Production\App\Models\CutTicket;
use Modules\Production\App\Models\PieceSerial;
use Modules\RawMaterial\App\Models\RawMaterial;
use Modules\Subcontract\App\Models\SubcontractLedgerEntry;
use Modules\Subcontract\App\Models\SubcontractOrder;

/**
 * PRD v1 §3.14/§4.13 — "a dedicated Reports section exposing seven
 * report types ... covering operational and financial data, generally
 * supporting date-range filtering." PRD v1 never enumerates the seven
 * by name beyond the Daily Cashbook, so this is a deliberate v1
 * interpretation (documented in Report/README.md "Known
 * simplifications"): five financial/operational aggregate reports
 * here, plus Daily Cashbook (delegates to Modules/Accounting, already
 * built in Phase 6) and Piece Traceability (TraceabilityService,
 * layering a friendlier read on Modules/Production's existing
 * piece-serials data per the PieceSerialController docblock).
 *
 * Every method here is READ-ONLY — no report ever writes anything. Each
 * one re-queries the owning module's tables directly rather than
 * duplicating business logic, following the same cross-module-read
 * precedent as Modules/Accounting's PartyFinancialsService.
 */
class ReportService
{
    /**
     * Financial + operational — orders in a date range, grand totals by
     * status. Date range applies to `created_at` (order intake date);
     * `contract_date` is an optional, often-empty field per PRD v1 §4.3.
     */
    public static function salesOrders(?string $from, ?string $to): array
    {
        $query = Order::query()->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        $byStatus = (clone $query)->selectRaw('status, COUNT(*) as order_count, SUM(grand_total) as total_value')
            ->groupBy('status')->get();

        return [
            'from' => $from,
            'to' => $to,
            'total_orders' => (clone $query)->count(),
            'total_value' => (string) ((clone $query)->sum('grand_total') ?: '0.00'),
            'by_status' => $byStatus,
        ];
    }

    /**
     * Operational — cutting/sewing/QC throughput in a date range.
     * Cut Tickets bucketed by `cut_date`; piece serials (the
     * traceability spine — sdd.md §5) bucketed by `created_at` since
     * that's when CuttingService::finalize() mints them, and by
     * `status` for a QC-pass/reject/pending breakdown.
     */
    public static function production(?string $from, ?string $to): array
    {
        $cutTickets = CutTicket::query()
            ->when($from, fn ($q) => $q->whereDate('cut_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('cut_date', '<=', $to));

        $pieces = PieceSerial::query()
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        return [
            'from' => $from,
            'to' => $to,
            'cut_tickets_count' => (clone $cutTickets)->count(),
            'planned_quantity' => (int) ((clone $cutTickets)->sum('planned_quantity') ?: 0),
            'pieces_by_status' => (clone $pieces)->selectRaw('status, COUNT(*) as piece_count')->groupBy('status')->get(),
        ];
    }

    /**
     * Operational — current stock levels. Raw material side reuses
     * RawMaterial::stockOn()/isBelowReorderLevel() (sdd.md §5 — both
     * already computed live off the movement ledger, never stored);
     * finished goods side is the same grouped-ledger aggregate
     * FinishedGoodsController::stock() uses, kept in sync deliberately.
     * Not date-ranged — stock is a point-in-time snapshot.
     */
    public static function stock(): array
    {
        $rawMaterials = RawMaterial::query()->where('is_active', true)->get()->map(fn (RawMaterial $rm) => [
            'id' => $rm->id,
            'name' => $rm->name,
            'unit' => $rm->unit,
            'stock' => $rm->stockOn(),
            'reorder_level' => (string) $rm->reorder_level,
            'below_reorder_level' => $rm->isBelowReorderLevel(),
        ]);

        $finishedGoods = FinishedGoodsMovement::query()
            ->selectRaw('location_id, order_id, style, color, size, SUM(quantity) as quantity')
            ->groupBy('location_id', 'order_id', 'style', 'color', 'size')
            ->havingRaw('SUM(quantity) > 0')
            ->get();

        return [
            'raw_materials' => $rawMaterials,
            'raw_materials_below_reorder' => $rawMaterials->where('below_reorder_level', true)->values(),
            'finished_goods' => $finishedGoods,
        ];
    }

    /**
     * Operational + financial — outward/inward subcontract activity in
     * a date range, plus job-work income recognized (mirrors
     * Modules/Subcontract's own ledger types, see SubcontractLedgerService).
     */
    public static function subcontract(?string $from, ?string $to): array
    {
        $orders = SubcontractOrder::query()
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        $ledger = SubcontractLedgerEntry::query()
            ->when($from, fn ($q) => $q->whereDate('occurred_on', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('occurred_on', '<=', $to));

        return [
            'from' => $from,
            'to' => $to,
            'orders_by_direction_status' => (clone $orders)
                ->selectRaw('direction, status, COUNT(*) as order_count')
                ->groupBy('direction', 'status')->get(),
            'job_work_income' => (string) ((clone $ledger)->where('type', 'job_work_income')->sum('amount') ?: '0.00'),
        ];
    }

    /**
     * Financial — per-party total_bill/paid/advance/due/balance, the
     * same figures Party Ledger/Party Due List show, laid out as one
     * exportable table. `$type` optionally scopes to buyers/suppliers/
     * subcontractors (Party::scopeBuyers()/scopeSuppliers()/
     * scopeSubcontractors()).
     */
    public static function partyLedger(?string $type): array
    {
        $query = Party::query()->where('is_active', true);
        if (in_array($type, ['buyer', 'supplier', 'subcontractor'], true)) {
            $query->where('type', $type);
        }

        return $query->get()->map(fn (Party $party) => array_merge(
            ['id' => $party->id, 'name' => $party->name, 'type' => $party->type],
            PartyFinancialsService::summarize($party),
        ))->all();
    }

    /**
     * Delegates to Modules\Accounting\App\Http\Controllers\CashbookController
     * — the Daily Cashbook is already fully built (Phase 6, PRD v1
     * §3.9/§4.8); the Report Suite just re-surfaces the same endpoint's
     * response under /reports/cashbook rather than duplicating the
     * running-summary computation.
     */
    public static function cashbook(Request $request): array
    {
        $response = App::make(CashbookController::class)->index($request);

        return json_decode($response->getContent(), true);
    }
}
