<?php

use Illuminate\Support\Facades\Route;
use Modules\Budgeting\App\Http\Controllers\BudgetController;

/*
|--------------------------------------------------------------------------
| Budgeting Module API Routes
|--------------------------------------------------------------------------
|
| PRD v1 §3.3 (Budgeting).
|
*/

Route::middleware('auth.api')->prefix('budgets')->group(function () {
    Route::get('/', [BudgetController::class, 'index'])
        ->middleware('permission:budgeting.view')->name('budgets.index');
    Route::get('/{budget}', [BudgetController::class, 'show'])
        ->middleware('permission:budgeting.view')->name('budgets.show');
    Route::post('/', [BudgetController::class, 'store'])
        ->middleware('permission:budgeting.create')->name('budgets.store');
    Route::put('/{budget}', [BudgetController::class, 'update'])
        ->middleware('permission:budgeting.edit')->name('budgets.update');
    Route::delete('/{budget}', [BudgetController::class, 'destroy'])
        ->middleware('permission:budgeting.delete')->name('budgets.destroy');
});
