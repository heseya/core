<?php

use App\Http\Controllers\MetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('options')->group(function (): void {
    Route::patch('id:{option:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:products.edit');
    Route::patch('id:{option:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:products.edit');
});
