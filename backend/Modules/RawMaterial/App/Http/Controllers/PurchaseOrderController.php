<?php

namespace Modules\RawMaterial\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\RawMaterial\App\Http\Requests\ReceivePurchaseOrderRequest;
use Modules\RawMaterial\App\Http\Requests\StorePurchaseOrderRequest;
use Modules\RawMaterial\App\Http\Resources\PurchaseOrderResource;
use Modules\RawMaterial\App\Models\RawMaterialPurchaseOrder;
use Modules\RawMaterial\App\Models\RawMaterialPurchaseOrderItem;
use Modules\RawMaterial\App\Services\PurchaseOrderNumberGenerator;
use Modules\RawMaterial\App\Services\RawMaterialStockService;

/**
 * PRD v2 §3.19 — Purchase Orders, evolving v1's Accessory Orders.
 */
class PurchaseOrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $orders = RawMaterialPurchaseOrder::query()
            ->with('items')
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(PurchaseOrderResource::collection($orders));
    }

    public function show(RawMaterialPurchaseOrder $purchaseOrder): JsonResponse
    {
        $purchaseOrder->load('items');

        return $this->ok(new PurchaseOrderResource($purchaseOrder));
    }

    public function store(StorePurchaseOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $items = $data['items'];

        $po = DB::transaction(function () use ($data, $items, $request) {
            $year = (int) now()->year;

            // Sequence must be resolved and assigned BEFORE the first
            // save() — `year`/`sequence_no` are NOT NULL columns with no
            // default (see the raw_material_purchase_orders migration),
            // so an initial create() without them would fail the insert
            // outright under strict SQL mode. One INSERT with every NOT
            // NULL column already populated, not two.
            $sequence = PurchaseOrderNumberGenerator::nextFor($year);

            $po = new RawMaterialPurchaseOrder([
                'supplier_id' => $data['supplier_id'],
                'location_id' => $data['location_id'],
                'status' => 'ordered',
                'order_date' => $data['order_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $request->user()->id,
            ]);
            $po->year = $year;
            $po->sequence_no = $sequence;
            $po->po_no = PurchaseOrderNumberGenerator::format($year, $sequence);
            $po->save();

            foreach ($items as $item) {
                $po->items()->create([
                    'raw_material_id' => $item['raw_material_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => round($item['quantity_ordered'] * $item['unit_price'], 2),
                ]);
            }

            return $po;
        });

        $po->load('items');

        return $this->created(new PurchaseOrderResource($po));
    }

    /**
     * Partial receipt supported — posts a `receipt` movement per received
     * item via RawMaterialStockService, bumps quantity_received, and
     * recomputes the PO's overall status.
     */
    public function receive(ReceivePurchaseOrderRequest $request, RawMaterialPurchaseOrder $purchaseOrder): JsonResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $purchaseOrder, $request) {
            foreach ($data['items'] as $received) {
                $item = RawMaterialPurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)
                    ->lockForUpdate()
                    ->findOrFail($received['item_id']);

                $outstanding = $item->outstandingQuantity();
                if (bccomp((string) $received['quantity'], $outstanding, 3) > 0) {
                    throw ValidationException::withMessages([
                        'items' => "Cannot receive {$received['quantity']} against item #{$item->id} — only {$outstanding} outstanding.",
                    ]);
                }

                RawMaterialStockService::receipt(
                    $item->rawMaterial,
                    $purchaseOrder->location,
                    (string) $received['quantity'],
                    $request->user()->id,
                    $purchaseOrder,
                );

                $item->quantity_received = bcadd((string) $item->quantity_received, (string) $received['quantity'], 3);
                $item->save();
            }

            $purchaseOrder->refreshStatus();
        });

        $purchaseOrder->load('items');

        return $this->ok(new PurchaseOrderResource($purchaseOrder));
    }
}
