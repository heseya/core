<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('cart')->group(function (): void {
    Route::post('process', [OrderController::class, 'cartProcess'])
        ->middleware('can:cart.verify');
});
