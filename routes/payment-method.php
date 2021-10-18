<?php

use App\Http\Controllers\PaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::prefix('payment-methods')->group(function (): void {
    Route::get(null, [PaymentMethodController::class, 'index'])
        ->middleware('can:payment_methods.show');
    Route::post(null, [PaymentMethodController::class, 'store'])
        ->middleware('can:payment_methods.add');
    Route::patch('id:{payment_method:id}', [PaymentMethodController::class, 'update'])
        ->middleware('can:payment_methods.edit');
    Route::delete('id:{payment_method:id}', [PaymentMethodController::class, 'destroy'])
        ->middleware('can:payment_methods.remove');
});
