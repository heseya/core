<?php

use App\Http\Controllers\PriceMapController;
use Illuminate\Support\Facades\Route;

Route::prefix('price-maps')->group(function (): void {
    Route::get('/', [PriceMapController::class, 'index'])
        ->middleware('can:price-maps.show');
    Route::post('/', [PriceMapController::class, 'store'])
        ->middleware('can:price-maps.add');
    Route::patch('id:{price_map:id}', [PriceMapController::class, 'update'])
        ->middleware('can:price-maps.edit');
    Route::delete('id:{price_map:id}', [PriceMapController::class, 'destroy'])
        ->middleware('can:price-maps.remove');
});
