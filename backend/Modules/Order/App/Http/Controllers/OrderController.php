<?php

namespace Modules\Order\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Order\App\Http\Requests\StoreOrderRequest;
use Modules\Order\App\Http\Requests\UpdateOrderRequest;
use Modules\Order\App\Http\Resources\OrderResource;
use Modules\Order\App\Models\Order;
use Modules\Order\App\Services\OrderNumberGenerator;

/**
 * PRD v1 §3.1 / §4.2 / §4.3 (Orders Management).
 */
class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $orders = Order::query()
            ->with(['party', 'merchandiser'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('party_id'), fn ($q) => $q->where('party_id', $request->integer('party_id')))
            ->when($request->filled('season'), fn ($q) => $q->where('season', $request->string('season')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($q2) => $q2->where('order_no', 'like', $term)->orWhere('title', 'like', $term));
            })
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(OrderResource::collection($orders));
    }

    public function show(Order $order): JsonResponse
    {
        $order->load(['party', 'merchandiser', 'lineItems']);

        return $this->ok(new OrderResource($order));
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $lineItems = $data['line_items'];
        unset($data['line_items']);

        if ($request->hasFile('image')) {
            $data['item_image_path'] = $request->file('image')->store('orders', 'local');
        }
        unset($data['image']);

        $order = DB::transaction(function () use ($data, $lineItems) {
            $order = Order::create($data);

            // order_no is derived from the row's own id (see
            // OrderNumberGenerator docblock) so it can only be set after
            // the insert above.
            $order->order_no = OrderNumberGenerator::generateFor($order);
            $order->save();

            foreach ($lineItems as $item) {
                $order->lineItems()->create([
                    'style' => $item['style'],
                    'color' => $item['color'],
                    'item' => $item['item'],
                    'shipment_date' => $item['shipment_date'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    // Never trust a client-sent total — always computed.
                    'total_price' => round($item['quantity'] * $item['unit_price'], 2),
                ]);
            }

            $order->recalculateGrandTotal();

            return $order;
        });

        $order->load(['party', 'merchandiser', 'lineItems']);

        return $this->created(new OrderResource($order));
    }

    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        $data = $request->validated();
        $lineItems = $data['line_items'] ?? null;
        unset($data['line_items']);

        if ($request->hasFile('image')) {
            if ($order->item_image_path) {
                Storage::disk('local')->delete($order->item_image_path);
            }
            $data['item_image_path'] = $request->file('image')->store('orders', 'local');
        }
        unset($data['image']);

        DB::transaction(function () use ($data, $lineItems, $order) {
            $order->fill($data);
            $order->save();

            // Full-replace semantics: this simple intake form has no
            // per-row ids for the client to reference individual existing
            // line items, so a `line_items` array on update replaces the
            // whole set (soft-deleted, not hard-deleted — sdd.md §5).
            // See README "Line item update semantics".
            if ($lineItems !== null) {
                $order->lineItems()->delete();

                foreach ($lineItems as $item) {
                    $order->lineItems()->create([
                        'style' => $item['style'],
                        'color' => $item['color'],
                        'item' => $item['item'],
                        'shipment_date' => $item['shipment_date'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => round($item['quantity'] * $item['unit_price'], 2),
                    ]);
                }

                $order->recalculateGrandTotal();
            }
        });

        $order->load(['party', 'merchandiser', 'lineItems']);

        return $this->ok(new OrderResource($order));
    }

    public function destroy(Order $order): JsonResponse
    {
        // sdd.md §5: soft delete only — Orders are the traceability root
        // for Booking/Budgeting/Costing/Sampling/Shipment/Production.
        $order->delete();

        return $this->noContent();
    }
}
