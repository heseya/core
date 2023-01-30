<?php

use App\Http\Controllers\BannerController;
use App\Http\Controllers\MetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('banners')->group(function (): void {
    Route::get(null, [BannerController::class, 'index'])
        ->middleware('can:banners.show');
    Route::get('/id:{banner:id}', [BannerController::class, 'show'])
        ->middleware('can:banners.show')
        ->whereUuid('banner');
    Route::post(null, [BannerController::class, 'store'])
        ->middleware('can:banners.add');
    Route::patch('/id:{banner:id}', [BannerController::class, 'update'])
        ->middleware('can:banners.edit')
        ->whereUuid('banner');
    Route::delete('/id:{banner:id}', [BannerController::class, 'destroy'])
        ->middleware('can:banners.remove')
        ->whereUuid('banner');
    Route::patch('id:{banner:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:banners.edit');
    Route::patch('id:{banner:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:banners.edit');
});
