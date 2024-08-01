<?php

use Domain\PaymentMethods\Controllers\PaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::prefix('payment-methods')->group(function (): void {
    Route::get('id:{payment_method:id}', [PaymentMethodController::class, 'show'])
        ->middleware('can:payment_methods.show_details');
    Route::get('/', [PaymentMethodController::class, 'index'])
        ->middleware('can:payment_methods.show');
    Route::post('/', [PaymentMethodController::class, 'store'])
        ->middleware(['can:payment_methods.add', 'user.restrict']);
    Route::patch('id:{payment_method:id}', [PaymentMethodController::class, 'update'])
        ->middleware(['can:payment_methods.edit', 'user.restrict']);
    Route::delete('id:{payment_method:id}', [PaymentMethodController::class, 'destroy'])
        ->middleware(['can:payment_methods.remove', 'user.restrict']);
});
