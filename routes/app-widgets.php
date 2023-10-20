<?php

use Domain\App\Controllers\AppWidgetController;
use Illuminate\Support\Facades\Route;

Route::prefix('app-widgets')->group(function (): void {
    Route::get('/', [AppWidgetController::class, 'index']);
    Route::post('/', [AppWidgetController::class, 'store'])
        ->middleware('can:app_widgets.add');
    Route::patch('id:{widget:id}', [AppWidgetController::class, 'update']);
    Route::delete('id:{widget:id}', [AppWidgetController::class, 'destroy']);
});
