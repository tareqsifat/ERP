<?php

use Illuminate\Support\Facades\Route;
use Modules\Sampling\App\Http\Controllers\SampleController;

/*
|--------------------------------------------------------------------------
| Sampling Module API Routes
|--------------------------------------------------------------------------
|
| PRD v1 §3.4 (Sampling).
|
*/

Route::middleware('auth.api')->prefix('samples')->group(function () {
    Route::get('/', [SampleController::class, 'index'])
        ->middleware('permission:sampling.view')->name('samples.index');
    Route::get('/{sample}', [SampleController::class, 'show'])
        ->middleware('permission:sampling.view')->name('samples.show');
    Route::post('/', [SampleController::class, 'store'])
        ->middleware('permission:sampling.create')->name('samples.store');
    Route::put('/{sample}', [SampleController::class, 'update'])
        ->middleware('permission:sampling.edit')->name('samples.update');
    Route::delete('/{sample}', [SampleController::class, 'destroy'])
        ->middleware('permission:sampling.delete')->name('samples.destroy');
});
