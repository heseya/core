<?php

use App\Http\Controllers\ProductSetController;
use Illuminate\Support\Facades\Route;

Route::prefix('product-sets')->group(function (): void {
    Route::get(null, [ProductSetController::class, 'index'])
        ->middleware('permission:product_sets.show|products.add|products.edit');
    Route::get('id:{product_set:id}', [ProductSetController::class, 'show'])
        ->middleware('can:product_sets.show_details');
    Route::get('{product_set:slug}', [ProductSetController::class, 'show'])
        ->middleware('can:product_sets.show_details');
    Route::post(null, [ProductSetController::class, 'store'])
        ->middleware('can:product_sets.add');
    Route::patch('id:{product_set:id}', [ProductSetController::class, 'update'])
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
});
