<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\MetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('sales-channels')->group(function (): void {
    Route::get('/', [AppController::class, 'index'])
        ->middleware('can:sales_channels.show');
    Route::post('/', [AppController::class, 'store'])
        ->middleware('can:sales_channels.add');
    Route::patch('/id:{id}', [AppController::class, 'update'])
        ->middleware('can:sales_channels.edit');
    Route::delete('/id:{id}', [AppController::class, 'destroy'])
        ->middleware('can:apps.remove');
});
