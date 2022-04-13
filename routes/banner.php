<?php

use App\Http\Controllers\BannerController;
use Illuminate\Support\Facades\Route;

Route::prefix('banners')->group(function (): void {
    Route::get(null, [BannerController::class, 'index'])
        ->middleware('can:banners.show');
    Route::post(null, [BannerController::class, 'store'])
        ->middleware('can:banners.add');
});
