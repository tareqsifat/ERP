<?php

use Illuminate\Support\Facades\Route;
use Modules\Production\App\Http\Controllers\BundleController;
use Modules\Production\App\Http\Controllers\CutTicketController;
use Modules\Production\App\Http\Controllers\LineController;
use Modules\Production\App\Http\Controllers\MachineController;
use Modules\Production\App\Http\Controllers\PieceSerialController;

/*
|--------------------------------------------------------------------------
| Production Module API Routes
|--------------------------------------------------------------------------
|
| PRD v2 §3.17/§3.18/§3.22 — Cutting, Sewing, QC, and the Machine/Line
| register (register lives here per sdd.md §2's repo layout, gated by
| `machine.*` permissions).
|
*/

Route::middleware('auth.api')->group(function () {
    Route::prefix('lines')->group(function () {
        Route::get('/', [LineController::class, 'index'])
            ->middleware('permission:machine.view')->name('lines.index');
        Route::get('/{line}', [LineController::class, 'show'])
            ->middleware('permission:machine.view')->name('lines.show');
        Route::post('/', [LineController::class, 'store'])
            ->middleware('permission:machine.create')->name('lines.store');
        Route::put('/{line}', [LineController::class, 'update'])
            ->middleware('permission:machine.edit')->name('lines.update');
        Route::delete('/{line}', [LineController::class, 'destroy'])
            ->middleware('permission:machine.delete')->name('lines.destroy');
    });

    Route::prefix('machines')->group(function () {
        Route::get('/', [MachineController::class, 'index'])
            ->middleware('permission:machine.view')->name('machines.index');
        Route::get('/{machine}', [MachineController::class, 'show'])
            ->middleware('permission:machine.view')->name('machines.show');
        Route::post('/', [MachineController::class, 'store'])
            ->middleware('permission:machine.create')->name('machines.store');
        Route::put('/{machine}', [MachineController::class, 'update'])
            ->middleware('permission:machine.edit')->name('machines.update');
        Route::delete('/{machine}', [MachineController::class, 'destroy'])
            ->middleware('permission:machine.delete')->name('machines.destroy');
    });

    Route::prefix('cut-tickets')->group(function () {
        Route::get('/', [CutTicketController::class, 'index'])
            ->middleware('permission:production.cutting.view')->name('cut-tickets.index');
        Route::get('/{cutTicket}', [CutTicketController::class, 'show'])
            ->middleware('permission:production.cutting.view')->name('cut-tickets.show');
        Route::post('/', [CutTicketController::class, 'store'])
            ->middleware('permission:production.cutting.create')->name('cut-tickets.store');
        Route::put('/{cutTicket}', [CutTicketController::class, 'update'])
            ->middleware('permission:production.cutting.create')->name('cut-tickets.update');
        Route::delete('/{cutTicket}', [CutTicketController::class, 'destroy'])
            ->middleware('permission:production.cutting.create')->name('cut-tickets.destroy');
        Route::post('/{cutTicket}/finalize', [CutTicketController::class, 'finalize'])
            ->middleware('permission:production.cutting.create')->name('cut-tickets.finalize');
    });

    Route::prefix('bundles')->group(function () {
        Route::get('/', [BundleController::class, 'index'])
            ->middleware('permission:production.sewing.view')->name('bundles.index');
        Route::get('/{bundle}', [BundleController::class, 'show'])
            ->middleware('permission:production.sewing.view')->name('bundles.show');
        Route::post('/{bundle}/assign-to-line', [BundleController::class, 'assignToLine'])
            ->middleware('permission:production.sewing.create')->name('bundles.assign-to-line');
        Route::post('/{bundle}/log-output', [BundleController::class, 'logOutput'])
            ->middleware('permission:production.sewing.create')->name('bundles.log-output');
    });

    Route::prefix('piece-serials')->group(function () {
        Route::get('/', [PieceSerialController::class, 'index'])
            ->middleware('permission:production.trace.view')->name('piece-serials.index');
        Route::get('/{pieceSerial}', [PieceSerialController::class, 'show'])
            ->middleware('permission:production.trace.view')->name('piece-serials.show');
        Route::post('/{pieceSerial}/qc', [PieceSerialController::class, 'qc'])
            ->middleware('permission:production.qc.record')->name('piece-serials.qc');
    });
});
