<?php

use Domain\Setting\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')->group(function (): void {
    Route::get('/', [SettingController::class, 'index'])
        ->middleware('can:settings.show');
    Route::get('{setting:name}', [SettingController::class, 'show'])
        ->middleware('can:settings.show_details');
    Route::post('/', [SettingController::class, 'store'])
        ->middleware('can:settings.add');
    Route::patch('{setting:name}', [SettingController::class, 'update'])
        ->middleware('can:settings.edit');
    Route::delete('{setting:name}', [SettingController::class, 'destroy'])
        ->middleware('can:settings.remove');
});
