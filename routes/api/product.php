<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function (): void {
    Route::get(null, [ProductController::class, 'index'])
        ->middleware('can:products.show');
    Route::post(null, [ProductController::class, 'store'])
        ->middleware('can:products.add');
    Route::get('id:{product:id}', [ProductController::class, 'show'])
        ->middleware('can:products.show_details');
    Route::get('{product:slug}', [ProductController::class, 'show'])
        ->middleware('can:products.show_details');
    Route::patch('id:{product:id}', [ProductController::class, 'update'])
        ->middleware('can:products.edit');
    Route::delete('id:{product:id}', [ProductController::class, 'destroy'])
        ->middleware('can:products.remove');
});