<?php

use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('wishlist')
    ->middleware('can:profile.wishlist_manage')
    ->group(function (): void {
        Route::get(null, [WishlistController::class, 'index']);
        Route::get('/id:{product:id}', [WishlistController::class, 'show']);
        Route::post(null, [WishlistController::class, 'store']);
        Route::delete('/id:{product:id}', [WishlistController::class, 'destroy']);
    });
