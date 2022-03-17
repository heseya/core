<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')->group(function (): void {
    Route::get(null, [OrderController::class, 'index'])
        ->middleware('can:orders.show');
    Route::post(null, [OrderController::class, 'store'])
        ->middleware('can:orders.add');
    Route::get('my', [OrderController::class, 'indexUserOrder'])
        ->middleware('can:orders.show_own');
    Route::get('my/{order:code}', [OrderController::class, 'showUserOrder'])
        ->middleware('can:orders.show_own');
    Route::post('verify', [OrderController::class, 'verify'])
        ->middleware('can:cart.verify');
    Route::get('id:{order:id}', [OrderController::class, 'show'])
        ->middleware('can:orders.show_details');
    Route::post('id:{order:id}/status', [OrderController::class, 'updateStatus'])
        ->middleware('can:orders.edit.status');
    Route::post('id:{order:id}/shipping-lists', [OrderController::class, 'shippingLists'])
        ->middleware('permission:orders.edit|orders.edit.status'); // !!
    Route::patch('id:{order:id}', [OrderController::class, 'update'])
        ->middleware('can:orders.edit');
    Route::get('{order:code}', [OrderController::class, 'showPublic'])
        ->middleware('can:orders.show_summary');

    Route::post('{order:code}/pay/offline', [PaymentController::class, 'offlinePayment'])
        ->middleware('can:payments.offline');
    Route::post('{order:code}/pay/{method}', [PaymentController::class, 'store'])
        ->middleware('can:payments.add');
});
