<?php

namespace Modules\Hrm\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Hrm\App\Http\Requests\StoreDesignationRequest;
use Modules\Hrm\App\Http\Requests\UpdateDesignationRequest;
use Modules\Hrm\App\Http\Resources\DesignationResource;
use Modules\Hrm\App\Models\Designation;

// PRD v1 §3.11/§4.10 — Designations master list.
class DesignationController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->ok(DesignationResource::collection(Designation::query()->orderBy('name')->get()));
    }

    public function store(StoreDesignationRequest $request): JsonResponse
    {
        $designation = Designation::create($request->validated());

        return $this->created(new DesignationResource($designation));
    }

    public function update(UpdateDesignationRequest $request, Designation $designation): JsonResponse
    {
        $designation->fill($request->validated());
        $designation->save();

        return $this->ok(new DesignationResource($designation));
    }

    public function destroy(Designation $designation): JsonResponse
    {
        $designation->delete();

        return $this->noContent();
    }
}
