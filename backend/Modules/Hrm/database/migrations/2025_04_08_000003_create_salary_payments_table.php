<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1 §3.11/§4.10/§7.5 — Salaries List: one row per employee per
 * payroll month, `paid_amount` incremented by each "Pay Salary" action
 * (App\Services\SalaryService::pay()) rather than an append-only
 * movement ledger — deliberately, because the PRD models this as a
 * single per-period summary row (SL, Employee, Month, Year, Salary
 * Amount, Paid Amount, Due Salary, Payment Method, Pay Date), not a
 * transaction log. `due_amount` is NOT stored — it's always
 * `salary_amount - paid_amount`, computed in SalaryPaymentResource, so
 * it can never drift out of sync with the two numbers it's derived
 * from. Explicitly NOT attendance-based (PRD v2 §7 flags attendance-
 * based payroll as Out of Scope for v1/v2) — this is flat salary
 * pay/due tracking only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('salary_amount', 15, 2); // snapshot of Employee.salary at creation
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->date('pay_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['employee_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
