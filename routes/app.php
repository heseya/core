<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AppWidgetController;
use App\Http\Controllers\MetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('apps')->group(function (): void {
    Route::get(null, [AppController::class, 'index'])
        ->middleware('can:apps.show');
    Route::get('id:{app:id}', [AppController::class, 'show'])
        ->middleware('can:apps.show_details')
        ->whereUuid('app');
    Route::post(null, [AppController::class, 'store'])
        ->middleware('can:apps.install');
    Route::patch('id:{app:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:apps.install');
    Route::patch('id:{app:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:apps.install');
    Route::delete('id:{app:id}', [AppController::class, 'destroy'])
        ->middleware('can:apps.remove');

    Route::prefix('widgets')->group(function (): void {
        Route::get(null, [AppWidgetController::class, 'index'])
            ->middleware('can:app_widgets.show');
        Route::post(null, [AppWidgetController::class, 'store'])
            ->middleware('can:app_widgets.add');
        Route::patch('id:{appWidget:id}', [AppWidgetController::class, 'update'])
            ->middleware('can:app_widgets.edit');
        Route::delete('id:{appWidget:id}', [AppWidgetController::class, 'destroy'])
            ->middleware('app_widgets.remove');
    });
});
