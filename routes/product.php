<?php

use App\Http\Controllers\MetadataController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')->group(function (): void {
    Route::get('/', [ProductController::class, 'index'])
        ->middleware('can:products.show');
    Route::post('/', [ProductController::class, 'store'])
        ->middleware('can:products.add');
    Route::get('id:{product:id}', [ProductController::class, 'show'])
        ->middleware('can:products.show_details', 'published:product');
    Route::get('{product:slug}', [ProductController::class, 'show'])
        ->middleware('can:products.show_details', 'published:product');
    Route::patch('id:{product:id}', [ProductController::class, 'update'])
        ->middleware('can:products.edit');
    Route::patch('id:{product:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:products.edit');
    Route::patch('id:{product:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:products.edit');
    Route::delete('id:{product:id}', [ProductController::class, 'destroy'])
        ->middleware('can:products.remove');
    Route::get('id:{product:id}/sales', [ProductController::class, 'showForDashboard'])
        ->middleware('can:products.show_details', 'published:product');

    Route::post('id:{product:id}/attachments', [ProductController::class, 'addAttachment'])
        ->middleware('can:products.edit');
    Route::patch('id:{product:id}/attachments/id:{attachment:id}', [ProductController::class, 'editAttachment'])
        ->middleware('can:products.edit');
    Route::delete('id:{product:id}/attachments/id:{attachment:id}', [ProductController::class, 'deleteAttachment'])
        ->middleware('can:products.edit');
});
