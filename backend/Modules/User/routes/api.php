<?php

use Illuminate\Support\Facades\Route;
use Modules\User\App\Http\Controllers\ProfileController;
use Modules\User\App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| User Module API Routes
|--------------------------------------------------------------------------
*/

// sdd.md §4 + failed_doc.md §1: 'auth.api' = auth:api + the is_active
// re-check (see bootstrap/app.php), not bare 'auth:api'.
Route::middleware('auth.api')->group(function () {
    Route::patch('/users/me', [ProfileController::class, 'update'])->name('users.me.update');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:user.view')->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])
        ->middleware('permission:user.view')->name('users.show');
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:user.create')->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:user.edit')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:user.delete')->name('users.destroy');
});
