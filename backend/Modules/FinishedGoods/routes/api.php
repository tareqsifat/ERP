<?php

use Illuminate\Support\Facades\Route;
use Modules\FinishedGoods\App\Http\Controllers\FinishedGoodsController;

/*
|--------------------------------------------------------------------------
| Finished Goods Module API Routes
|--------------------------------------------------------------------------
|
| PRD v2 §3.20 (Finished Goods Inventory). Read-only — stock only moves
| as a side effect of QC pass (Production), Stock Transfer (Location), or
| Shipment.
|
*/

Route::middleware('auth.api')->prefix('finished-goods')->group(function () {
    Route::get('/stock', [FinishedGoodsController::class, 'stock'])
        ->middleware('permission:finished-goods.view')->name('finished-goods.stock');
    Route::get('/movements', [FinishedGoodsController::class, 'movements'])
        ->middleware('permission:finished-goods.view')->name('finished-goods.movements');
});
