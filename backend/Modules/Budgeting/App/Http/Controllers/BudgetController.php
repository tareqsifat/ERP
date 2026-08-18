<?php

namespace Modules\Budgeting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Budgeting\App\Http\Requests\StoreBudgetRequest;
use Modules\Budgeting\App\Http\Requests\UpdateBudgetRequest;
use Modules\Budgeting\App\Http\Resources\BudgetResource;
use Modules\Budgeting\App\Models\Budget;

/**
 * PRD v1 §3.3 (Budgeting).
 */
class BudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $budgets = Budget::query()
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(BudgetResource::collection($budgets));
    }

    public function show(Budget $budget): JsonResponse
    {
        return $this->ok(new BudgetResource($budget));
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $data = $request->validated();
        // sdd.md §5: never trust a client-sent total — always computed.
        $data['total_value'] = round($data['budgeted_quantity'] * $data['average_unit_price'], 2);

        $budget = Budget::create($data);

        return $this->created(new BudgetResource($budget));
    }

    public function update(UpdateBudgetRequest $request, Budget $budget): JsonResponse
    {
        $data = $request->validated();

        $budget->fill($data);

        if (array_key_exists('budgeted_quantity', $data) || array_key_exists('average_unit_price', $data)) {
            $budget->total_value = round($budget->budgeted_quantity * $budget->average_unit_price, 2);
        }

        $budget->save();

        return $this->ok(new BudgetResource($budget));
    }

    public function destroy(Budget $budget): JsonResponse
    {
        $budget->delete();

        return $this->noContent();
    }
}
