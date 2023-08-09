<?php

use App\Http\Controllers\MetadataController;
use Domain\Order\Controllers\OrderStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('statuses')->group(function (): void {
    Route::get('/', [OrderStatusController::class, 'index'])
        ->middleware('can:statuses.show');
    Route::post('/', [OrderStatusController::class, 'store'])
        ->middleware('can:statuses.add');
    Route::patch('id:{status:id}', [OrderStatusController::class, 'update'])
        ->middleware('can:statuses.edit');
    Route::patch('id:{status:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:statuses.edit');
    Route::patch('id:{status:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:statuses.edit');
    Route::post('reorder', [OrderStatusController::class, 'reorder'])
        ->middleware('can:statuses.edit');
    Route::delete('id:{status:id}', [OrderStatusController::class, 'destroy'])
        ->middleware('can:statuses.remove');
});
