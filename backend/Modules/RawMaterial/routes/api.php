<?php

use Illuminate\Support\Facades\Route;
use Modules\RawMaterial\App\Http\Controllers\PurchaseOrderController;
use Modules\RawMaterial\App\Http\Controllers\RawMaterialController;
use Modules\RawMaterial\App\Http\Controllers\RawMaterialStockMovementController;

/*
|--------------------------------------------------------------------------
| Raw Material Module API Routes
|--------------------------------------------------------------------------
|
| PRD v2 §3.19 (Raw Material Inventory).
|
*/

Route::middleware('auth.api')->group(function () {
    Route::prefix('raw-materials')->group(function () {
        Route::get('/', [RawMaterialController::class, 'index'])
            ->middleware('permission:raw-material.view')->name('raw-materials.index');
        Route::get('/{rawMaterial}', [RawMaterialController::class, 'show'])
            ->middleware('permission:raw-material.view')->name('raw-materials.show');
        Route::post('/', [RawMaterialController::class, 'store'])
            ->middleware('permission:raw-material.create')->name('raw-materials.store');
        Route::put('/{rawMaterial}', [RawMaterialController::class, 'update'])
            ->middleware('permission:raw-material.edit')->name('raw-materials.update');
        Route::delete('/{rawMaterial}', [RawMaterialController::class, 'destroy'])
            ->middleware('permission:raw-material.delete')->name('raw-materials.destroy');
    });

    Route::prefix('raw-material-movements')->group(function () {
        Route::get('/', [RawMaterialStockMovementController::class, 'index'])
            ->middleware('permission:raw-material.view')->name('raw-material-movements.index');
        Route::post('/', [RawMaterialStockMovementController::class, 'store'])
            ->middleware('permission:raw-material.edit')->name('raw-material-movements.store');
    });

    Route::prefix('raw-material-purchase-orders')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])
            ->middleware('permission:raw-material.purchase-order.manage')->name('raw-material-purchase-orders.index');
        Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
            ->middleware('permission:raw-material.purchase-order.manage')->name('raw-material-purchase-orders.show');
        Route::post('/', [PurchaseOrderController::class, 'store'])
            ->middleware('permission:raw-material.purchase-order.manage')->name('raw-material-purchase-orders.store');
        Route::post('/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])
            ->middleware('permission:raw-material.purchase-order.manage')->name('raw-material-purchase-orders.receive');
    });
});
