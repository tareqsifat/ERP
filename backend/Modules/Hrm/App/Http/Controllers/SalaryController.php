<?php

namespace Modules\Hrm\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Hrm\App\Http\Requests\OpenSalaryMonthRequest;
use Modules\Hrm\App\Http\Requests\PaySalaryRequest;
use Modules\Hrm\App\Http\Resources\SalaryPaymentResource;
use Modules\Hrm\App\Models\Employee;
use Modules\Hrm\App\Models\SalaryPayment;
use Modules\Hrm\App\Services\SalaryService;

// PRD v1 §3.11/§4.10/§7.5 — Salaries List + "Pay Salary" action.
class SalaryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $payments = SalaryPayment::query()
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('month'), fn ($q) => $q->where('month', $request->integer('month')))
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->integer('year')))
            ->orderByDesc('year')->orderByDesc('month')
            ->paginate($perPage);

        return $this->ok(SalaryPaymentResource::collection($payments));
    }

    public function open(OpenSalaryMonthRequest $request): JsonResponse
    {
        $employee = Employee::findOrFail($request->validated('employee_id'));

        $payment = SalaryService::openMonth(
            $employee,
            (int) $request->validated('month'),
            (int) $request->validated('year'),
            $request->user()->id,
        );

        return $this->created(new SalaryPaymentResource($payment));
    }

    public function pay(PaySalaryRequest $request, SalaryPayment $salaryPayment): JsonResponse
    {
        $payment = SalaryService::pay(
            $salaryPayment,
            (string) $request->validated('amount'),
            $request->validated('payment_method'),
            $request->validated('pay_date'),
        );

        return $this->ok(new SalaryPaymentResource($payment));
    }
}
