<?php

use App\Http\Controllers\MediaController;
use App\Http\Controllers\MetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('media')->group(function (): void {
    $auth = 'permission:pages.add|pages.edit|products.add|products.edit|seo.edit|product_sets.add|product_sets.edit';

    Route::post(null, [MediaController::class, 'store'])
        ->middleware($auth);
    Route::patch('id:{media:id}', [MediaController::class, 'update'])
        ->middleware($auth);
    Route::patch('id:{media:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware($auth);
    Route::patch('id:{media:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware($auth);
    Route::delete('id:{media:id}', [MediaController::class, 'destroy'])
        ->middleware($auth);
});
