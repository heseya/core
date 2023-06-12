<?php

use App\Http\Controllers\MetadataController;
use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('statuses')->group(function (): void {
    Route::get('/', [StatusController::class, 'index'])
        ->middleware('can:statuses.show');
    Route::post('/', [StatusController::class, 'store'])
        ->middleware('can:statuses.add');
    Route::patch('id:{status:id}', [StatusController::class, 'update'])
        ->middleware('can:statuses.edit');
    Route::patch('id:{status:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:statuses.edit');
    Route::patch('id:{status:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:statuses.edit');
    Route::post('reorder', [StatusController::class, 'reorder'])
        ->middleware('can:statuses.edit');
    Route::delete('id:{status:id}', [StatusController::class, 'destroy'])
        ->middleware('can:statuses.remove');
});
