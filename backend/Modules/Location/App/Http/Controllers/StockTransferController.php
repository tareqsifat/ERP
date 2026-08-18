<?php

namespace Modules\Location\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Location\App\Http\Requests\ReceiveStockTransferRequest;
use Modules\Location\App\Http\Requests\StoreStockTransferRequest;
use Modules\Location\App\Http\Resources\StockTransferResource;
use Modules\Location\App\Models\Location;
use Modules\Location\App\Models\StockTransfer;
use Modules\Location\App\Services\StockTransferService;
use Modules\Order\App\Models\Order;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * PRD v2 §3.21 — Stock Transfer dispatch/receive between locations.
 *
 * sdd.md §4: "Location-scoping (Showroom Staff sees only their showroom)
 * is not a role — it's a location_id on the user record, checked in
 * policies/queries." A user with a non-null `location_id` (Showroom
 * Staff) is confined to transfers that touch their own location; a user
 * with `location_id = null` (Admin, Store Keeper) sees/acts on all
 * transfers, matching how the role grants themselves are not
 * location-specific.
 */
class StockTransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);
        $scopedLocationId = $request->user()->location_id;

        $transfers = StockTransfer::query()
            ->when($scopedLocationId, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('from_location_id', $scopedLocationId)
                ->orWhere('to_location_id', $scopedLocationId)))
            ->when($request->filled('from_location_id'), fn ($q) => $q->where('from_location_id', $request->integer('from_location_id')))
            ->when($request->filled('to_location_id'), fn ($q) => $q->where('to_location_id', $request->integer('to_location_id')))
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(StockTransferResource::collection($transfers));
    }

    public function show(Request $request, StockTransfer $stockTransfer): JsonResponse
    {
        $this->guardLocationScope($request->user(), $stockTransfer);

        return $this->ok(new StockTransferResource($stockTransfer));
    }

    public function store(StoreStockTransferRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->user()->location_id && (int) $request->user()->location_id !== (int) $data['from_location_id']) {
            throw new AccessDeniedHttpException('You may only dispatch stock from your own location.');
        }

        $transfer = StockTransferService::dispatch(
            Location::findOrFail($data['from_location_id']),
            Location::findOrFail($data['to_location_id']),
            Order::findOrFail($data['order_id']),
            $data['style'],
            $data['color'],
            $data['size'] ?? null,
            $data['quantity'],
            $request->user()->id,
        );

        return $this->created(new StockTransferResource($transfer));
    }

    public function receive(ReceiveStockTransferRequest $request, StockTransfer $stockTransfer): JsonResponse
    {
        $this->guardLocationScope($request->user(), $stockTransfer);

        $transfer = StockTransferService::receive(
            $stockTransfer,
            $request->validated('quantity_received'),
            $request->user()->id,
        );

        return $this->ok(new StockTransferResource($transfer));
    }

    /**
     * A Showroom Staff user (location_id set) may only read/receive a
     * transfer that touches their own location — neither endpoint of a
     * transfer between two *other* locations is visible to them.
     */
    private function guardLocationScope(User $user, StockTransfer $transfer): void
    {
        if (! $user->location_id) {
            return;
        }

        if ((int) $user->location_id !== (int) $transfer->from_location_id
            && (int) $user->location_id !== (int) $transfer->to_location_id) {
            throw new AccessDeniedHttpException('This stock transfer does not belong to your location.');
        }
    }
}
