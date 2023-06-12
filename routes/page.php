<?php

use App\Http\Controllers\MetadataController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::prefix('pages')->group(function (): void {
    Route::get('/', [PageController::class, 'index'])
        ->middleware('can:pages.show');
    Route::post('/', [PageController::class, 'store'])
        ->middleware('can:pages.add');
    Route::get('id:{page:id}', [PageController::class, 'show'])
        ->middleware('can:pages.show_details');
    Route::get('{page:slug}', [PageController::class, 'show'])
        ->middleware('can:pages.show_details');
    Route::patch('id:{page:id}', [PageController::class, 'update'])
        ->middleware('can:pages.edit');
    Route::patch('id:{page:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:pages.edit');
    Route::patch('id:{page:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:pages.edit');
    Route::delete('id:{page:id}', [PageController::class, 'destroy'])
        ->middleware('can:pages.remove');
    Route::post('reorder', [PageController::class, 'reorder'])
        ->middleware('can:pages.edit');
});
