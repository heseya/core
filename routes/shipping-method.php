<?php

use App\Http\Controllers\MetadataController;
use App\Http\Controllers\ShippingMethodController;
use Illuminate\Support\Facades\Route;

Route::prefix('shipping-methods')->group(function (): void {
    Route::get(null, [ShippingMethodController::class, 'index'])
        ->middleware('can:shipping_methods.show');
    Route::post(null, [ShippingMethodController::class, 'store'])
        ->middleware('can:shipping_methods.add');
    Route::patch('id:{shipping_method:id}', [ShippingMethodController::class, 'update'])
        ->middleware('can:shipping_methods.edit');
    Route::patch('id:{shipping_method:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:shipping_methods.edit');
    Route::patch('id:{shipping_method:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:shipping_methods.edit');
    Route::delete('id:{shipping_method:id}', [ShippingMethodController::class, 'destroy'])
        ->middleware('can:shipping_methods.remove');
    Route::post('reorder', [ShippingMethodController::class, 'reorder'])
        ->middleware('can:shipping_methods.edit');
});
