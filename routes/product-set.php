<?php

use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\MetadataController;
use App\Http\Controllers\ProductSetController;
use Illuminate\Support\Facades\Route;

Route::prefix('product-sets')->group(function (): void {
    Route::prefix('favourites')
        ->middleware('can:profile.favourites_manage')
        ->group(function (): void {
            Route::get('/', [FavouriteController::class, 'index']);
            Route::get('id:{product_set:id}', [FavouriteController::class, 'show']);
            Route::post('/', [FavouriteController::class, 'store']);
            Route::delete('id:{product_set:id}', [FavouriteController::class, 'destroy']);
            Route::delete('/', [FavouriteController::class, 'destroyAll']);
        });

    Route::get('/', [ProductSetController::class, 'index'])
        ->middleware('permission:product_sets.show|products.add|products.edit');
    Route::get('id:{product_set:id}', [ProductSetController::class, 'show'])
        ->middleware('can:product_sets.show_details');
    Route::get('{product_set:slug}', [ProductSetController::class, 'show'])
        ->middleware('can:product_sets.show_details');
    Route::post('/', [ProductSetController::class, 'store'])
        ->middleware('can:product_sets.add');
    Route::patch('id:{product_set:id}', [ProductSetController::class, 'update'])
        ->middleware('can:product_sets.edit');
    Route::patch('id:{product_set:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:product_sets.edit');
    Route::patch('id:{product_set:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:product_sets.edit');
    Route::post('reorder', [ProductSetController::class, 'reorder'])
        ->middleware('can:product_sets.edit');
    Route::post('reorder/id:{product_set:id}', [ProductSetController::class, 'reorder'])
        ->middleware('can:product_sets.edit');
    Route::delete('id:{product_set:id}', [ProductSetController::class, 'destroy'])
        ->middleware('can:product_sets.remove');

    Route::get('id:{product_set:id}/products', [ProductSetController::class, 'products'])
        ->middleware('can:product_sets.show_details');
    Route::post('id:{product_set:id}/products', [ProductSetController::class, 'attach'])
        ->middleware('can:product_sets.edit');
    Route::post('id:{product_set:id}/products/reorder', [ProductSetController::class, 'reorderProducts'])
        ->middleware('can:product_sets.edit');
});
