<?php

use Illuminate\Support\Facades\Route;
use Modules\Party\App\Http\Controllers\PartyController;

/*
|--------------------------------------------------------------------------
| Party Module API Routes
|--------------------------------------------------------------------------
|
| PRD v1 §3.10 (Buyer/Supplier), extended by PRD v2 §4.9 (+ Subcontractor).
|
*/

Route::middleware('auth.api')->prefix('parties')->group(function () {
    Route::get('/', [PartyController::class, 'index'])
        ->middleware('permission:party.view')->name('parties.index');
    Route::get('/{party}', [PartyController::class, 'show'])
        ->middleware('permission:party.view')->name('parties.show');
    Route::post('/', [PartyController::class, 'store'])
        ->middleware('permission:party.create')->name('parties.store');
    Route::put('/{party}', [PartyController::class, 'update'])
        ->middleware('permission:party.edit')->name('parties.update');
    Route::delete('/{party}', [PartyController::class, 'destroy'])
        ->middleware('permission:party.delete')->name('parties.destroy');
});
