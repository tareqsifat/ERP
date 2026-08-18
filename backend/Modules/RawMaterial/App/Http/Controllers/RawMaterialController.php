<?php

namespace Modules\RawMaterial\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Location\App\Models\Location;
use Modules\RawMaterial\App\Http\Requests\StoreRawMaterialRequest;
use Modules\RawMaterial\App\Http\Requests\UpdateRawMaterialRequest;
use Modules\RawMaterial\App\Http\Resources\RawMaterialResource;
use Modules\RawMaterial\App\Models\RawMaterial;

/**
 * PRD v2 §3.19 (Raw Material Master).
 */
class RawMaterialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $materials = RawMaterial::query()
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->string('search').'%');
            })
            ->orderBy('name')
            ->paginate($perPage);

        if ($request->boolean('low_stock_only')) {
            $materials->setCollection(
                $materials->getCollection()->filter->isBelowReorderLevel()->values()
            );
        }

        return $this->ok(RawMaterialResource::collection($materials));
    }

    public function show(Request $request, RawMaterial $rawMaterial): JsonResponse
    {
        if ($request->boolean('with_stock')) {
            $location = $request->filled('location_id') ? Location::find($request->integer('location_id')) : null;
            $rawMaterial->current_stock = $rawMaterial->stockOn($location);
        }

        return $this->ok(new RawMaterialResource($rawMaterial));
    }

    public function store(StoreRawMaterialRequest $request): JsonResponse
    {
        $material = RawMaterial::create($request->validated());

        return $this->created(new RawMaterialResource($material));
    }

    public function update(UpdateRawMaterialRequest $request, RawMaterial $rawMaterial): JsonResponse
    {
        $rawMaterial->fill($request->validated());
        $rawMaterial->save();

        return $this->ok(new RawMaterialResource($rawMaterial));
    }

    public function destroy(RawMaterial $rawMaterial): JsonResponse
    {
        $rawMaterial->delete();

        return $this->noContent();
    }
}
