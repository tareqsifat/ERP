<?php

use Illuminate\Support\Facades\Route;
use Modules\Report\App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Report Module API Routes
|--------------------------------------------------------------------------
|
| PRD v1 §3.14/§4.13 — Reports section, seven report types (Phase 7 of
| todo.md). All gated by the single `report.view` permission — this is
| a read-only aggregate view layered on other modules' data, not a
| module with its own write-side authorization story.
|
*/

Route::middleware('auth.api')->prefix('reports')->group(function () {
    Route::get('/', [ReportController::class, 'index'])
        ->middleware('permission:report.view')->name('reports.index');
    Route::get('/sales-orders', [ReportController::class, 'salesOrders'])
        ->middleware('permission:report.view')->name('reports.sales-orders');
    Route::get('/production', [ReportController::class, 'production'])
        ->middleware('permission:report.view')->name('reports.production');
    Route::get('/stock', [ReportController::class, 'stock'])
        ->middleware('permission:report.view')->name('reports.stock');
    Route::get('/subcontract', [ReportController::class, 'subcontract'])
        ->middleware('permission:report.view')->name('reports.subcontract');
    Route::get('/party-ledger', [ReportController::class, 'partyLedger'])
        ->middleware('permission:report.view')->name('reports.party-ledger');
    Route::get('/cashbook', [ReportController::class, 'cashbook'])
        ->middleware('permission:report.view')->name('reports.cashbook');
    Route::get('/traceability/{serial}', [ReportController::class, 'traceability'])
        ->middleware('permission:report.view')->name('reports.traceability');
});
