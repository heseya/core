<?php

use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('statuses')->group(function (): void {
    Route::get(null, [StatusController::class, 'index'])
        ->middleware('can:statuses.show');
    Route::post(null, [StatusController::class, 'store'])
        ->middleware('can:statuses.add');
    Route::patch('id:{status:id}', [StatusController::class, 'update'])
        ->middleware('can:statuses.edit');
    Route::post('order', [StatusController::class, 'order'])
        ->middleware('can:statuses.edit');
    Route::delete('id:{status:id}', [StatusController::class, 'destroy'])
        ->middleware('can:statuses.remove');
});
