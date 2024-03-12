<?php

use Domain\PersonalPrice\PersonalPriceController;
use Illuminate\Support\Facades\Route;

Route::prefix('prices')->group(function (): void {
    Route::get('products', [PersonalPriceController::class, 'productPrices'])
        ->middleware('can:products.show');
});
