<?php

use Domain\Wishlist\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('wishlist')
    ->middleware('can:profile.wishlist_manage')
    ->group(function (): void {
        Route::get('/', [WishlistController::class, 'index']);
        Route::get('/check', [WishlistController::class, 'check']);
        Route::get('/id:{product:id}', [WishlistController::class, 'show']);
        Route::post('/', [WishlistController::class, 'store']);
        Route::delete('/id:{product:id}', [WishlistController::class, 'destroy']);
        Route::delete('/', [WishlistController::class, 'destroyAll']);
    });
