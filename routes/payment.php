<?php

use App\Http\Controllers\PaymentController;
use App\Http\Middleware\IsAppPayment;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->group(function (): void {
    Route::get('id:{payment:id}', [PaymentController::class, 'show'])
        ->middleware('can:payments.show_details');
    Route::get('/', [PaymentController::class, 'index'])
        ->middleware('can:payments.show');
    Route::post('/', [PaymentController::class, 'store'])
        ->middleware('can:payments.add');

    Route::patch('id:{payment:id}', [PaymentController::class, 'updatePayment'])
        ->middleware(['permission:payments.edit', IsAppPayment::class]);

    // legacy endpoint for communication with payments providers
    Route::any('{method}', [PaymentController::class, 'updatePaymentLegacy']);
});
