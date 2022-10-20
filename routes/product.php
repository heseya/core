<?php

use App\Http\Controllers\MetadataController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function (): void {
    Route::get(null, [ProductController::class, 'index'])
        ->middleware('can:products.show');
    Route::post(null, [ProductController::class, 'store'])
        ->middleware('can:products.add');
    Route::get('id:{product:id}', [ProductController::class, 'show'])
        ->middleware('can:products.show_details')
        ->whereUuid('product');
    Route::get('{product:slug}', [ProductController::class, 'show'])
        ->middleware('can:products.show_details')
        ->where('product', '^[a-z0-9]+(?:-[a-z0-9]+)*$');
    Route::patch('id:{product:id}', [ProductController::class, 'update'])
        ->middleware('can:products.edit');
    Route::patch('id:{product:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:products.edit');
    Route::patch('id:{product:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:products.edit');
    Route::delete('id:{product:id}', [ProductController::class, 'destroy'])
        ->middleware('can:products.remove');
});
