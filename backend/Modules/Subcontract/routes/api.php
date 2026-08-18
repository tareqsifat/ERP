<?php

use Illuminate\Support\Facades\Route;
use Modules\Subcontract\App\Http\Controllers\SubcontractOrderController;

/*
|--------------------------------------------------------------------------
| Subcontract Module API Routes
|--------------------------------------------------------------------------
|
| Included by backend/routes/api.php under the /api/v1 prefix (sdd.md §3).
| PRD v2 §3.23/§3.24 — Outward and Inward Subcontract Orders share one
| resource (`direction` is the structural difference); read endpoints
| accept any of the three `subcontract.*` permissions, write endpoints
| are split outward/inward per PermissionSeeder's catalogue.
|
*/

Route::middleware('auth.api')->prefix('subcontract-orders')->group(function () {
    Route::get('/', [SubcontractOrderController::class, 'index'])
        ->middleware('permission:subcontract.outward.manage|subcontract.inward.manage|subcontract.ledger.view')
        ->name('subcontract-orders.index');
    Route::get('/{subcontractOrder}', [SubcontractOrderController::class, 'show'])
        ->middleware('permission:subcontract.outward.manage|subcontract.inward.manage|subcontract.ledger.view')
        ->name('subcontract-orders.show');
    Route::post('/', [SubcontractOrderController::class, 'store'])
        ->middleware('permission:subcontract.outward.manage|subcontract.inward.manage')
        ->name('subcontract-orders.store');

    // Outward-only actions (PRD v2 §3.23).
    Route::post('/{subcontractOrder}/issue-pieces', [SubcontractOrderController::class, 'issuePieces'])
        ->middleware('permission:subcontract.outward.manage')
        ->name('subcontract-orders.issue-pieces');
    Route::post('/{subcontractOrder}/issue-raw-material', [SubcontractOrderController::class, 'issueRawMaterial'])
        ->middleware('permission:subcontract.outward.manage')
        ->name('subcontract-orders.issue-raw-material');
    Route::post('/{subcontractOrder}/return-pieces', [SubcontractOrderController::class, 'returnPieces'])
        ->middleware('permission:subcontract.outward.manage')
        ->name('subcontract-orders.return-pieces');

    // Inward-only action (PRD v2 §3.24).
    Route::post('/{subcontractOrder}/dispatch-back', [SubcontractOrderController::class, 'dispatchBack'])
        ->middleware('permission:subcontract.inward.manage')
        ->name('subcontract-orders.dispatch-back');

    // Ledger (both directions).
    Route::get('/{subcontractOrder}/ledger', [SubcontractOrderController::class, 'ledger'])
        ->middleware('permission:subcontract.outward.manage|subcontract.inward.manage|subcontract.ledger.view')
        ->name('subcontract-orders.ledger');
    Route::post('/{subcontractOrder}/payment', [SubcontractOrderController::class, 'payment'])
        ->middleware('permission:subcontract.outward.manage|subcontract.inward.manage')
        ->name('subcontract-orders.payment');
});
