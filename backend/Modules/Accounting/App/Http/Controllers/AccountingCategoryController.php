<?php

namespace Modules\Accounting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\App\Http\Requests\StoreAccountingCategoryRequest;
use Modules\Accounting\App\Http\Requests\UpdateAccountingCategoryRequest;
use Modules\Accounting\App\Http\Resources\AccountingCategoryResource;
use Modules\Accounting\App\Models\AccountingCategory;

// PRD v1 §3.9/§4.8 — Income/Expense category masters.
class AccountingCategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = AccountingCategory::query()
            ->when($request->filled('kind'), fn ($q) => $q->where('kind', $request->string('kind')))
            ->orderBy('name')
            ->get();

        return $this->ok(AccountingCategoryResource::collection($categories));
    }

    public function store(StoreAccountingCategoryRequest $request): JsonResponse
    {
        $category = AccountingCategory::create($request->validated());

        return $this->created(new AccountingCategoryResource($category));
    }

    public function update(UpdateAccountingCategoryRequest $request, AccountingCategory $accountingCategory): JsonResponse
    {
        $accountingCategory->fill($request->validated());
        $accountingCategory->save();

        return $this->ok(new AccountingCategoryResource($accountingCategory));
    }

    public function destroy(AccountingCategory $accountingCategory): JsonResponse
    {
        $accountingCategory->delete();

        return $this->noContent();
    }
}
