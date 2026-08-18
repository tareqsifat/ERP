<?php

namespace Modules\Costing\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Costing\App\Http\Requests\StoreCostingRequest;
use Modules\Costing\App\Http\Requests\UpdateCostingRequest;
use Modules\Costing\App\Http\Resources\CostingResource;
use Modules\Costing\App\Models\Costing;

/**
 * PRD v1 §3.3 (Costing).
 */
class CostingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $costings = Costing::query()
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(CostingResource::collection($costings));
    }

    public function show(Costing $costing): JsonResponse
    {
        return $this->ok(new CostingResource($costing));
    }

    public function store(StoreCostingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['total_cost'] = round($data['costed_quantity'] * $data['average_unit_cost'], 2);

        $costing = Costing::create($data);

        return $this->created(new CostingResource($costing));
    }

    public function update(UpdateCostingRequest $request, Costing $costing): JsonResponse
    {
        $data = $request->validated();

        $costing->fill($data);

        if (array_key_exists('costed_quantity', $data) || array_key_exists('average_unit_cost', $data)) {
            $costing->total_cost = round($costing->costed_quantity * $costing->average_unit_cost, 2);
        }

        $costing->save();

        return $this->ok(new CostingResource($costing));
    }

    public function destroy(Costing $costing): JsonResponse
    {
        $costing->delete();

        return $this->noContent();
    }
}
