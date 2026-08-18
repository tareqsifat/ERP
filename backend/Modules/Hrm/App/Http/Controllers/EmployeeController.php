<?php

namespace Modules\Hrm\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Hrm\App\Http\Requests\StoreEmployeeRequest;
use Modules\Hrm\App\Http\Requests\UpdateEmployeeRequest;
use Modules\Hrm\App\Http\Resources\EmployeeResource;
use Modules\Hrm\App\Models\Employee;

// PRD v1 §3.11/§4.10/§5.5 — Employee directory.
class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $employees = Employee::query()
            ->when($request->filled('designation_id'), fn ($q) => $q->where('designation_id', $request->integer('designation_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('full_name')
            ->paginate($perPage);

        return $this->ok(EmployeeResource::collection($employees));
    }

    public function show(Employee $employee): JsonResponse
    {
        return $this->ok(new EmployeeResource($employee));
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $data = $request->validated();

        // sdd.md §8: stored outside the public web root, same contract as Party's image.
        if ($request->hasFile('id_document')) {
            $data['id_document_path'] = $request->file('id_document')->store('employees', 'local');
        }
        if ($request->hasFile('id_document_back')) {
            $data['id_document_back_path'] = $request->file('id_document_back')->store('employees', 'local');
        }
        unset($data['id_document'], $data['id_document_back']);

        $employee = new Employee($data);
        $employee->created_by = $request->user()->id;
        $employee->save();

        return $this->created(new EmployeeResource($employee));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('id_document')) {
            $data['id_document_path'] = $request->file('id_document')->store('employees', 'local');
        }
        if ($request->hasFile('id_document_back')) {
            $data['id_document_back_path'] = $request->file('id_document_back')->store('employees', 'local');
        }
        unset($data['id_document'], $data['id_document_back']);

        $employee->fill($data);
        $employee->save();

        return $this->ok(new EmployeeResource($employee));
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();

        return $this->noContent();
    }
}
