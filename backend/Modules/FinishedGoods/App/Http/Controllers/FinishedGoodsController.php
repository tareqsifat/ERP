<?php

namespace Modules\FinishedGoods\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\FinishedGoods\App\Http\Resources\FinishedGoodsMovementResource;
use Modules\FinishedGoods\App\Http\Resources\FinishedGoodsStockResource;
use Modules\FinishedGoods\App\Models\FinishedGoodsMovement;

/**
 * PRD v2 §3.20 — Finished Goods Inventory. Read-only: stock only ever
 * changes as a side effect of QC pass (Production), Stock Transfer
 * (Location), or Shipment — never a direct write here.
 */
class FinishedGoodsController extends Controller
{
    /**
     * Grouped ledger aggregate — {location, order, style, color, size} =>
     * sum(quantity) — filtered to non-zero balances. sdd.md §5: this is
     * a live SUM() over the ledger, not a stored stock table.
     */
    public function stock(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 50), 200);

        // sdd.md §4: location-scoping is a plain `location_id` on the
        // user record, not a role — a Showroom Staff user (location_id
        // set) is force-scoped to their own location regardless of what
        // `location_id` the request asks for, rather than trusting the
        // client-supplied filter (failed_doc.md §2 IDOR-class check).
        $scopedLocationId = $request->user()->location_id ?: $request->integer('location_id') ?: null;

        $query = FinishedGoodsMovement::query()
            ->selectRaw('location_id, order_id, style, color, size, SUM(quantity) as quantity')
            ->when($scopedLocationId, fn ($q) => $q->where('location_id', $scopedLocationId))
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->groupBy('location_id', 'order_id', 'style', 'color', 'size')
            ->havingRaw('SUM(quantity) > 0');

        $stock = $query->paginate($perPage);

        return $this->ok(FinishedGoodsStockResource::collection($stock));
    }

    public function movements(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        // See stock() above — same location-scoping rule.
        $scopedLocationId = $request->user()->location_id ?: $request->integer('location_id') ?: null;

        $movements = FinishedGoodsMovement::query()
            ->when($scopedLocationId, fn ($q) => $q->where('location_id', $scopedLocationId))
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(FinishedGoodsMovementResource::collection($movements));
    }
}
