<?php

use App\Http\Controllers\DiscountController;
use App\Http\Controllers\MetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('coupons')->group(function (): void {
    Route::get(null, [DiscountController::class, 'indexCoupons'])
        ->middleware('can:coupons.show');
    Route::get('id:{coupon:id}', [DiscountController::class, 'showCoupon'])
        ->middleware('can:coupons.show_details');
    Route::get('{coupon:code}', [DiscountController::class, 'showCoupon'])
        ->middleware('can:coupons.show_details')
        ->whereAlphaNumeric('coupon');
    Route::post(null, [DiscountController::class, 'storeCoupon'])
        ->middleware('can:coupons.add');
    Route::patch('id:{coupon:id}', [DiscountController::class, 'updateCoupon'])
        ->middleware('can:coupons.edit');
    Route::delete('id:{coupon:id}', [DiscountController::class, 'destroyCoupon'])
        ->middleware('can:coupons.remove');
    Route::patch('id:{coupon:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:coupons.edit');
    Route::patch('id:{coupon:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:coupons.edit');
});

Route::prefix('sales')->group(function (): void {
    Route::get(null, [DiscountController::class, 'indexSales'])
        ->middleware('can:sales.show');
    Route::get('id:{sale:id}', [DiscountController::class, 'showSale'])
        ->middleware('can:sales.show_details');
    Route::post(null, [DiscountController::class, 'storeSale'])
        ->middleware('can:sales.add');
    Route::patch('id:{sale:id}', [DiscountController::class, 'updateSale'])
        ->middleware('can:sales.edit');
    Route::delete('id:{sale:id}', [DiscountController::class, 'destroySale'])
        ->middleware('can:sales.remove');
    Route::patch('id:{sale:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:sales.edit');
    Route::patch('id:{sale:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:sales.edit');
});
