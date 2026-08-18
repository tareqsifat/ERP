<?php

use Illuminate\Support\Facades\Route;
use Modules\Booking\App\Http\Controllers\BookingController;

/*
|--------------------------------------------------------------------------
| Booking Module API Routes
|--------------------------------------------------------------------------
|
| PRD v1 §3.2 (Booking Management).
|
*/

Route::middleware('auth.api')->prefix('bookings')->group(function () {
    Route::get('/', [BookingController::class, 'index'])
        ->middleware('permission:booking.view')->name('bookings.index');
    Route::get('/{booking}', [BookingController::class, 'show'])
        ->middleware('permission:booking.view')->name('bookings.show');
    Route::post('/', [BookingController::class, 'store'])
        ->middleware('permission:booking.create')->name('bookings.store');
    Route::put('/{booking}', [BookingController::class, 'update'])
        ->middleware('permission:booking.edit')->name('bookings.update');
    Route::delete('/{booking}', [BookingController::class, 'destroy'])
        ->middleware('permission:booking.delete')->name('bookings.destroy');
});
