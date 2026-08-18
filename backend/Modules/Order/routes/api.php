<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| Order Module API Routes
|--------------------------------------------------------------------------
|
| PRD v1 §3.1 (Orders Management).
|
*/

Route::middleware('auth.api')->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index'])
        ->middleware('permission:order.view')->name('orders.index');
    Route::get('/{order}', [OrderController::class, 'show'])
        ->middleware('permission:order.view')->name('orders.show');
    Route::post('/', [OrderController::class, 'store'])
        ->middleware('permission:order.create')->name('orders.store');
    Route::put('/{order}', [OrderController::class, 'update'])
        ->middleware('permission:order.edit')->name('orders.update');
    Route::delete('/{order}', [OrderController::class, 'destroy'])
        ->middleware('permission:order.delete')->name('orders.destroy');
});
