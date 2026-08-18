<?php

use Illuminate\Support\Facades\Route;
use Modules\Costing\App\Http\Controllers\CostingController;

/*
|--------------------------------------------------------------------------
| Costing Module API Routes
|--------------------------------------------------------------------------
|
| PRD v1 §3.3 (Costing — mirrors Budgeting's structure).
|
*/

Route::middleware('auth.api')->prefix('costings')->group(function () {
    Route::get('/', [CostingController::class, 'index'])
        ->middleware('permission:costing.view')->name('costings.index');
    Route::get('/{costing}', [CostingController::class, 'show'])
        ->middleware('permission:costing.view')->name('costings.show');
    Route::post('/', [CostingController::class, 'store'])
        ->middleware('permission:costing.create')->name('costings.store');
    Route::put('/{costing}', [CostingController::class, 'update'])
        ->middleware('permission:costing.edit')->name('costings.update');
    Route::delete('/{costing}', [CostingController::class, 'destroy'])
        ->middleware('permission:costing.delete')->name('costings.destroy');
});
