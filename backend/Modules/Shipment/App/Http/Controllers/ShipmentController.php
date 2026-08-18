<?php

namespace Modules\Shipment\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shipment\App\Http\Requests\StoreShipmentRequest;
use Modules\Shipment\App\Http\Requests\UpdateShipmentRequest;
use Modules\Shipment\App\Http\Resources\ShipmentResource;
use Modules\Shipment\App\Models\Shipment;
use Modules\Shipment\App\Services\ShipmentInvoiceNumberGenerator;

/**
 * PRD v1 §3.6 (Shipments).
 */
class ShipmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $shipments = Shipment::query()
            ->with(['order', 'creator'])
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(ShipmentResource::collection($shipments));
    }

    public function show(Shipment $shipment): JsonResponse
    {
        $shipment->load(['order', 'creator']);

        return $this->ok(new ShipmentResource($shipment));
    }

    public function store(StoreShipmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $shipment = DB::transaction(function () use ($data) {
            $year = (int) now()->year;

            // Sequence must be resolved and assigned BEFORE the first
            // save() — `year`/`sequence_no` are NOT NULL columns with no
            // default (see the shipments migration), so an initial
            // Shipment::create($data) without them would fail the
            // insert outright under strict SQL mode. One INSERT with
            // every NOT NULL column already populated, not two.
            $sequence = ShipmentInvoiceNumberGenerator::nextFor($year);

            $shipment = new Shipment($data);
            $shipment->year = $year;
            $shipment->sequence_no = $sequence;
            $shipment->invoice_no = ShipmentInvoiceNumberGenerator::format($year, $sequence);
            $shipment->save();

            return $shipment;
        });

        $shipment->load(['order', 'creator']);

        return $this->created(new ShipmentResource($shipment));
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): JsonResponse
    {
        $shipment->fill($request->validated());
        $shipment->save();

        $shipment->load(['order', 'creator']);

        return $this->ok(new ShipmentResource($shipment));
    }

    public function destroy(Shipment $shipment): JsonResponse
    {
        $shipment->delete();

        return $this->noContent();
    }
}
