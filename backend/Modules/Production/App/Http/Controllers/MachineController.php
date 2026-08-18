<?php

namespace Modules\Production\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Production\App\Http\Requests\StoreMachineRequest;
use Modules\Production\App\Http\Requests\UpdateMachineRequest;
use Modules\Production\App\Http\Resources\MachineResource;
use Modules\Production\App\Models\Machine;

/**
 * PRD v2 §3.22 / §4.7 — register + assignment only, not real-time IoT
 * monitoring (explicitly Out of Scope, PRD v2 §7).
 */
class MachineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 50), 100);

        $machines = Machine::query()
            ->when($request->filled('line_id'), fn ($q) => $q->where('line_id', $request->integer('line_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('tag')
            ->paginate($perPage);

        return $this->ok(MachineResource::collection($machines));
    }

    public function show(Machine $machine): JsonResponse
    {
        return $this->ok(new MachineResource($machine));
    }

    public function store(StoreMachineRequest $request): JsonResponse
    {
        $machine = Machine::create($request->validated());

        return $this->created(new MachineResource($machine));
    }

    public function update(UpdateMachineRequest $request, Machine $machine): JsonResponse
    {
        $machine->fill($request->validated());
        $machine->save();

        return $this->ok(new MachineResource($machine));
    }

    public function destroy(Machine $machine): JsonResponse
    {
        $machine->delete();

        return $this->noContent();
    }
}
