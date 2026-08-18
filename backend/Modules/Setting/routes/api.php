<?php

use Illuminate\Support\Facades\Route;
use Modules\Setting\App\Http\Controllers\SettingController;

/*
|--------------------------------------------------------------------------
| Setting Module API Routes
|--------------------------------------------------------------------------
|
| PRD v1 §3.15/§4.13 — Currency/Notification/System/Company Settings
| (Phase 7 of todo.md). Every authenticated user can read settings
| (the app's own UI needs, e.g., the currency format); only
| `setting.manage` holders (Admin only by default — see RoleSeeder) can
| change them.
|
*/

Route::middleware('auth.api')->prefix('settings')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/', [SettingController::class, 'update'])
        ->middleware('permission:setting.manage')->name('settings.update');
});
