<?php

namespace Modules\Hrm\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Hrm\App\Models\Employee;
use Modules\Hrm\App\Models\SalaryPayment;

/**
 * PRD v1 §3.11/§7.5 — "User navigates to Salaries List, selects an
 * employee/month, clicks Pay Salary... system records the payment,
 * updating Due Salary for that period." `openMonth()` creates the
 * per-period row (idempotent — one row per employee+month+year, see the
 * salary_payments migration's unique constraint); `pay()` adds to it.
 * Deliberately NOT attendance-based — see the salary_payments
 * migration's docblock and PRD v2 §7's explicit Out-of-Scope callout.
 */
class SalaryService
{
    public static function openMonth(Employee $employee, int $month, int $year, int $createdBy): SalaryPayment
    {
        return SalaryPayment::query()->firstOrCreate(
            ['employee_id' => $employee->id, 'month' => $month, 'year' => $year],
            ['salary_amount' => $employee->salary, 'created_by' => $createdBy],
        );
    }

    public static function pay(SalaryPayment $payment, string $amount, string $paymentMethod, ?string $payDate = null): SalaryPayment
    {
        if (bccomp($amount, '0', 2) <= 0) {
            throw ValidationException::withMessages(['amount' => 'The payment amount must be greater than zero.']);
        }

        $newPaid = bcadd((string) $payment->paid_amount, $amount, 2);
        if (bccomp($newPaid, (string) $payment->salary_amount, 2) > 0) {
            throw ValidationException::withMessages([
                'amount' => "This payment would overpay the {$payment->month}/{$payment->year} salary for this employee.",
            ]);
        }

        return DB::transaction(function () use ($payment, $newPaid, $paymentMethod, $payDate) {
            $payment->paid_amount = $newPaid;
            $payment->payment_method = $paymentMethod;
            $payment->pay_date = $payDate ?: now()->toDateString();
            $payment->save();

            return $payment;
        });
    }
}
