<?php

use Application\PriceMap\Controllers\PriceMapController;
use Illuminate\Support\Facades\Route;

Route::prefix('price-maps')->group(function (): void {
    Route::get('/', [PriceMapController::class, 'index'])->middleware('can:price-maps.show');
    Route::post('/', [PriceMapController::class, 'store'])->middleware('can:price-maps.add');
    Route::patch('id:{price_map:id}', [PriceMapController::class, 'update'])->middleware('can:price-maps.edit');
    Route::delete('id:{price_map:id}', [PriceMapController::class, 'destroy'])->middleware('can:price-maps.remove');
});

Route::prefix('price-maps')->group(function (): void {
    Route::get('id:{price_map:id}/prices', [PriceMapController::class, 'searchPrices'])->middleware('can:price-maps.show_details');
    Route::patch('id:{price_map:id}/prices', [PriceMapController::class, 'updatePrices'])->middleware('can:price-maps.edit');
});

Route::prefix('products')->group(function (): void {
    Route::get('id:{product:id}/prices', [PriceMapController::class, 'listProductPrices'])->middleware('can:products.show', 'can:price-maps.show_details');
    Route::patch('id:{product:id}/prices', [PriceMapController::class, 'updateProductPrices'])->middleware('can:products.edit', 'can:price-maps.edit');
});

Route::prefix('schemas')->group(function (): void {
    Route::get('id:{schema:id}/prices', [PriceMapController::class, 'listSchemaPrices'])->middleware('can:schemas.show', 'can:price-maps.show_details');
    Route::patch('id:{schema:id}/prices', [PriceMapController::class, 'updateSchemaPrices'])->middleware('can:schemas.edit', 'can:price-maps.edit');
});
