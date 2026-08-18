<?php

namespace Modules\RawMaterial\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Location\App\Models\Location;
use Modules\RawMaterial\App\Http\Requests\StoreStockAdjustmentRequest;
use Modules\RawMaterial\App\Http\Resources\RawMaterialStockMovementResource;
use Modules\RawMaterial\App\Models\RawMaterial;
use Modules\RawMaterial\App\Models\RawMaterialStockMovement;
use Modules\RawMaterial\App\Services\RawMaterialStockService;

/**
 * Read access to the ledger (index) + the one write action a human is
 * allowed to trigger directly: a manual adjustment. Receipts and issues
 * are always side effects of another action (PO receipt, Cutting) —
 * see those controllers — never posted here directly.
 */
class RawMaterialStockMovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $movements = RawMaterialStockMovement::query()
            ->when($request->filled('raw_material_id'), fn ($q) => $q->where('raw_material_id', $request->integer('raw_material_id')))
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', $request->integer('location_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(RawMaterialStockMovementResource::collection($movements));
    }

    public function store(StoreStockAdjustmentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $rawMaterial = RawMaterial::findOrFail($data['raw_material_id']);
        $location = Location::findOrFail($data['location_id']);

        $movement = RawMaterialStockService::adjustment(
            $rawMaterial,
            $location,
            (string) $data['quantity'],
            $request->user()->id,
            $data['remarks'],
        );

        return $this->created(new RawMaterialStockMovementResource($movement));
    }
}
