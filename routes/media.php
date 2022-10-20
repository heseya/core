<?php

use App\Http\Controllers\MediaController;
use App\Http\Controllers\MetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('media')->group(function (): void {
    Route::get(null, [MediaController::class, 'index'])
        ->middleware('can:media.show');
    Route::post(null, [MediaController::class, 'store'])
        ->middleware('can:media.add');
    Route::patch('id:{media:id}', [MediaController::class, 'update'])
        ->middleware('can:media.edit');
    Route::patch('id:{media:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:media.edit');
    Route::patch('id:{media:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:media.edit');
    Route::delete('id:{media:id}', [MediaController::class, 'destroy'])
        ->middleware('can:media.remove');
});
