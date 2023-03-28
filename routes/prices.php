<?php

use App\Http\Controllers\PriceController;
use Illuminate\Support\Facades\Route;

Route::prefix('prices')->group(function (): void {
    Route::get('products', [PriceController::class, 'productPrices'])
        ->middleware('can:products.show');
});
