<?php

use Modules\Hrm\App\Models\Designation;
use Modules\Hrm\App\Models\Employee;

// PRD v1 §3.11/§4.10/§7.5 — Designations, Employees, Salaries. Flat
// salary pay/due tracking, deliberately not attendance-based (PRD v2
// §7's explicit Out of Scope callout — see Modules/Hrm/README.md).

test('creating a designation and an employee against it', function () {
    actingAsRole('Accountant');

    $designation = $this->postJson('/api/v1/designations', ['name' => 'Sewing Operator'])
        ->assertCreated()->json('data');

    $response = $this->postJson('/api/v1/employees', [
        'full_name' => 'Rahim Uddin',
        'joining_date' => now()->toDateString(),
        'designation_id' => $designation['id'],
        'salary' => 12000,
    ]);

    $response->assertCreated();
    expect($response->json('data.status'))->toBe('active');
});

test('opening a salary month and paying it in two installments tracks the running due', function () {
    actingAsRole('Accountant');
    $designation = Designation::factory()->create();
    $employee = Employee::factory()->create(['designation_id' => $designation->id, 'salary' => 12000]);

    $payment = $this->postJson('/api/v1/salaries/open', [
        'employee_id' => $employee->id,
        'month' => now()->month,
        'year' => now()->year,
    ])->assertCreated()->json('data');

    expect($payment['salary_amount'])->toBe('12000.00');
    expect($payment['due_amount'])->toBe('12000.00');

    // Opening the same month twice is idempotent (firstOrCreate) — no
    // duplicate row, no double salary_amount.
    $this->postJson('/api/v1/salaries/open', [
        'employee_id' => $employee->id,
        'month' => now()->month,
        'year' => now()->year,
    ])->assertCreated();
    $this->assertDatabaseCount('salary_payments', 1);

    $this->postJson("/api/v1/salaries/{$payment['id']}/pay", [
        'amount' => 5000,
        'payment_method' => 'cash',
    ])->assertOk()->assertJsonPath('data.due_amount', '7000.00');

    $response = $this->postJson("/api/v1/salaries/{$payment['id']}/pay", [
        'amount' => 7000,
        'payment_method' => 'bank',
    ]);
    $response->assertOk()->assertJsonPath('data.due_amount', '0.00');
    expect($response->json('data.paid_amount'))->toBe('12000.00');
});

test('paying more than the remaining due is rejected', function () {
    actingAsRole('Accountant');
    $employee = Employee::factory()->create(['salary' => 10000]);

    $payment = $this->postJson('/api/v1/salaries/open', [
        'employee_id' => $employee->id,
        'month' => now()->month,
        'year' => now()->year,
    ])->assertCreated()->json('data');

    $this->postJson("/api/v1/salaries/{$payment['id']}/pay", [
        'amount' => 10001,
        'payment_method' => 'cash',
    ])->assertStatus(422);
});

test('a user without hrm.salary.pay cannot pay a salary', function () {
    actingAsRole('Showroom Staff');
    $employee = Employee::factory()->create();

    $payment = \Modules\Hrm\App\Models\SalaryPayment::factory()->create(['employee_id' => $employee->id]);

    $this->postJson("/api/v1/salaries/{$payment->id}/pay", [
        'amount' => 100,
        'payment_method' => 'cash',
    ])->assertStatus(403);
});
