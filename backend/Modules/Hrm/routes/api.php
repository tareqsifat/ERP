<?php

use Illuminate\Support\Facades\Route;
use Modules\Hrm\App\Http\Controllers\DesignationController;
use Modules\Hrm\App\Http\Controllers\EmployeeController;
use Modules\Hrm\App\Http\Controllers\SalaryController;

/*
|--------------------------------------------------------------------------
| Hrm Module API Routes
|--------------------------------------------------------------------------
|
| Included by backend/routes/api.php under the /api/v1 prefix (sdd.md §3).
| PRD v1 §3.11/§4.10/§7.5 — Designations, Employees, Salaries. Flat salary
| pay/due tracking only — attendance-based payroll is Out of Scope for
| v1/v2 (PRD v2 §7); `hrm.attendance.manage` stays an unused catalogue
| permission for a future phase (see Modules/Hrm/README.md).
|
*/

Route::middleware('auth.api')->group(function () {
    Route::prefix('designations')->middleware('permission:hrm.designation.manage')->group(function () {
        Route::get('/', [DesignationController::class, 'index'])->name('designations.index');
        Route::post('/', [DesignationController::class, 'store'])->name('designations.store');
        Route::put('/{designation}', [DesignationController::class, 'update'])->name('designations.update');
        Route::delete('/{designation}', [DesignationController::class, 'destroy'])->name('designations.destroy');
    });

    Route::prefix('employees')->middleware('permission:hrm.employee.manage')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('employees.index');
        Route::get('/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::post('/', [EmployeeController::class, 'store'])->name('employees.store');
        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    });

    Route::prefix('salaries')->group(function () {
        Route::get('/', [SalaryController::class, 'index'])
            ->middleware('permission:hrm.salary.view')->name('salaries.index');
        Route::post('/open', [SalaryController::class, 'open'])
            ->middleware('permission:hrm.salary.pay')->name('salaries.open');
        Route::post('/{salaryPayment}/pay', [SalaryController::class, 'pay'])
            ->middleware('permission:hrm.salary.pay')->name('salaries.pay');
    });
});
