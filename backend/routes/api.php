<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| sdd.md §3: this file does ONLY this — it never defines a business route
| itself. Each Laravel Module owns its own routes/api.php, included below.
| Keep the require list alphabetical-by-dependency-order to make it obvious
| where a new module goes.
|
| Convention: every module route that needs an authenticated user uses
| the 'auth.api' middleware GROUP (defined in bootstrap/app.php), never
| bare 'auth:api' — 'auth.api' also re-checks is_active on every request
| (failed_doc.md §1). Add 'permission:<name>' / 'role:<name>' on top of
| that per-route as needed.
|
*/

Route::prefix('v1')->group(function () {

    // sdd.md §6 — unauthenticated liveness probe, defined here directly
    // (not via a module) so there is always a one-line way to prove the
    // API process is up without needing a token.
    Route::get('/health', function () {
        return response()->json([
            'data' => [
                'status' => 'ok',
                'service' => 'garments-erp-api',
                'time' => now()->toIso8601String(),
            ],
        ]);
    })->name('api.health');

    require module_path('Auth', 'routes/api.php');
    require module_path('User', 'routes/api.php');
    require module_path('Party', 'routes/api.php');
    require module_path('Order', 'routes/api.php');
    require module_path('Booking', 'routes/api.php');
    require module_path('Budgeting', 'routes/api.php');
    require module_path('Costing', 'routes/api.php');
    require module_path('Sampling', 'routes/api.php');
    require module_path('Shipment', 'routes/api.php');
    require module_path('Location', 'routes/api.php');
    require module_path('RawMaterial', 'routes/api.php');
    require module_path('Production', 'routes/api.php');
    require module_path('FinishedGoods', 'routes/api.php');
    require module_path('Subcontract', 'routes/api.php');
    require module_path('Accounting', 'routes/api.php');
    require module_path('Hrm', 'routes/api.php');
    require module_path('Report', 'routes/api.php');
    require module_path('Setting', 'routes/api.php');
});
