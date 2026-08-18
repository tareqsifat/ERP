<?php

use Illuminate\Support\Facades\Route;
use Modules\Location\App\Http\Controllers\LocationController;
use Modules\Location\App\Http\Controllers\StockTransferController;

/*
|--------------------------------------------------------------------------
| Location Module API Routes
|--------------------------------------------------------------------------
|
| PRD v2 §3.21 (Locations & Stock Transfer — both live in this module per
| sdd.md §2's repo layout).
|
*/

Route::middleware('auth.api')->prefix('locations')->group(function () {
    Route::get('/', [LocationController::class, 'index'])
        ->middleware('permission:location.view')->name('locations.index');
    Route::get('/{location}', [LocationController::class, 'show'])
        ->middleware('permission:location.view')->name('locations.show');
    Route::post('/', [LocationController::class, 'store'])
        ->middleware('permission:location.create')->name('locations.store');
    Route::put('/{location}', [LocationController::class, 'update'])
        ->middleware('permission:location.edit')->name('locations.update');
    Route::delete('/{location}', [LocationController::class, 'destroy'])
        ->middleware('permission:location.delete')->name('locations.destroy');
});

Route::middleware('auth.api')->prefix('stock-transfers')->group(function () {
    Route::get('/', [StockTransferController::class, 'index'])
        ->middleware('permission:stock-transfer.view')->name('stock-transfers.index');
    Route::get('/{stockTransfer}', [StockTransferController::class, 'show'])
        ->middleware('permission:stock-transfer.view')->name('stock-transfers.show');
    Route::post('/', [StockTransferController::class, 'store'])
        ->middleware('permission:stock-transfer.dispatch')->name('stock-transfers.store');
    Route::post('/{stockTransfer}/receive', [StockTransferController::class, 'receive'])
        ->middleware('permission:stock-transfer.receive')->name('stock-transfers.receive');
});
