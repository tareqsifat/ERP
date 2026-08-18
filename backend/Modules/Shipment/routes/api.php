<?php

use Illuminate\Support\Facades\Route;
use Modules\Shipment\App\Http\Controllers\ShipmentController;

/*
|--------------------------------------------------------------------------
| Shipment Module API Routes
|--------------------------------------------------------------------------
|
| PRD v1 §3.6 (Shipments).
|
*/

Route::middleware('auth.api')->prefix('shipments')->group(function () {
    Route::get('/', [ShipmentController::class, 'index'])
        ->middleware('permission:shipment.view')->name('shipments.index');
    Route::get('/{shipment}', [ShipmentController::class, 'show'])
        ->middleware('permission:shipment.view')->name('shipments.show');
    Route::post('/', [ShipmentController::class, 'store'])
        ->middleware('permission:shipment.create')->name('shipments.store');
    Route::put('/{shipment}', [ShipmentController::class, 'update'])
        ->middleware('permission:shipment.edit')->name('shipments.update');
    Route::delete('/{shipment}', [ShipmentController::class, 'destroy'])
        ->middleware('permission:shipment.delete')->name('shipments.destroy');
});
