<?php

namespace Modules\Location\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Location\App\Http\Requests\StoreLocationRequest;
use Modules\Location\App\Http\Requests\UpdateLocationRequest;
use Modules\Location\App\Http\Resources\LocationResource;
use Modules\Location\App\Models\Location;

/**
 * PRD v2 §3.21 (Locations & Stock Transfer — this controller covers the
 * register only; StockTransfer itself is StockTransferController in this
 * same module per sdd.md §2's repo layout).
 */
class LocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 50), 100);

        $locations = Location::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate($perPage);

        return $this->ok(LocationResource::collection($locations));
    }

    public function show(Location $location): JsonResponse
    {
        return $this->ok(new LocationResource($location));
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        $location = Location::create($request->validated());

        return $this->created(new LocationResource($location));
    }

    public function update(UpdateLocationRequest $request, Location $location): JsonResponse
    {
        $location->fill($request->validated());
        $location->save();

        return $this->ok(new LocationResource($location));
    }

    public function destroy(Location $location): JsonResponse
    {
        $location->delete();

        return $this->noContent();
    }
}
