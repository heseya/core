<?php

use App\Http\Controllers\DiscountController;
use App\Http\Controllers\MetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('discounts')->group(function (): void {
    Route::get(null, [DiscountController::class, 'index'])
        ->middleware('can:discounts.show');
    Route::get('{discount:code}', [DiscountController::class, 'show'])
        ->middleware('can:discounts.show_details');
    Route::post(null, [DiscountController::class, 'store'])
        ->middleware('can:discounts.add');
    Route::patch('id:{discount:id}', [DiscountController::class, 'update'])
        ->middleware('can:discounts.edit');
    Route::patch('id:{discount:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:discounts.edit');
    Route::patch('id:{discount:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:discounts.edit');
    Route::delete('id:{discount:id}', [DiscountController::class, 'destroy'])
        ->middleware('can:discounts.remove');
});
