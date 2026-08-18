<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Auth Module API Routes
|--------------------------------------------------------------------------
|
| Included under /api/v1 by backend/routes/api.php (sdd.md §3).
|
*/

Route::prefix('auth')->name('auth.')->group(function () {
    // failed_doc.md §1: rate-limited to blunt credential stuffing.
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login');

    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->middleware('throttle:login')
        ->name('refresh');

    // sdd.md §4 + failed_doc.md §1: 'auth.api' = auth:api + the
    // is_active re-check (see bootstrap/app.php), not bare 'auth:api'.
    Route::middleware('auth.api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });
});
