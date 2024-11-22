<?php

use Domain\Manufacturer\Controllers\ManufacturerController;
use Illuminate\Support\Facades\Route;

Route::prefix('manufacturers')->group(function (): void {
    Route::get('/', [ManufacturerController::class, 'index'])
        ->middleware('can:manufacturers.show');
    Route::post('/', [ManufacturerController::class, 'store'])
        ->middleware('can:manufacturers.add');
    Route::get('/id:{manufacturer:id}', [ManufacturerController::class, 'show'])
        ->middleware('can:manufacturers.show_details');
    Route::patch('/id:{manufacturer:id}', [ManufacturerController::class, 'update'])
        ->middleware('can:manufacturers.edit');
    Route::delete('/id:{manufacturer:id}', [ManufacturerController::class, 'destroy'])
        ->middleware('can:manufacturers.remove');
});
