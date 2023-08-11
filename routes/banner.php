<?php

use App\Http\Controllers\MetadataController;
use Domain\Banner\Controllers\BannerController;
use Illuminate\Support\Facades\Route;

Route::prefix('banners')->group(function (): void {
    Route::get('/', [BannerController::class, 'index'])
        ->middleware('can:banners.show');
    Route::get('/id:{banner:id}', [BannerController::class, 'show'])
        ->middleware('can:banners.show');
    Route::get('/{banner:slug}', [BannerController::class, 'show'])
        ->middleware('can:banners.show');
    Route::post('/', [BannerController::class, 'store'])
        ->middleware('can:banners.add');
    Route::patch('/id:{banner:id}', [BannerController::class, 'update'])
        ->middleware('can:banners.edit');
    Route::delete('/id:{banner:id}', [BannerController::class, 'destroy'])
        ->middleware('can:banners.remove');
    Route::patch('id:{banner:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:banners.edit');
    Route::patch('id:{banner:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:banners.edit');
});
