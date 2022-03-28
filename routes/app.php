<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\MetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('apps')->group(function (): void {
    Route::get(null, [AppController::class, 'index'])
        ->middleware('can:apps.show');
    Route::get('id:{app:id}', [AppController::class, 'show'])
        ->middleware('can:apps.show_details');
    Route::post(null, [AppController::class, 'store'])
        ->middleware('can:apps.install');
    Route::patch('id:{app:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:apps.install');
    Route::patch('id:{app:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:apps.install');
    Route::delete('id:{app:id}', [AppController::class, 'destroy'])
        ->middleware('can:apps.remove');
});
