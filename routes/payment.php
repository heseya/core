<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('payments')->group(function (): void {
    Route::post(null, [PaymentController::class, 'store'])
        ->middleware('can:payments.add');
    Route::get('id:{payment:id}', [PaymentController::class, 'show'])
        ->middleware('can:payments.show_details');
    Route::get(null, [PaymentController::class, 'index'])
        ->middleware('can:payments.show');
    Route::patch('id:{payment:id}', [PaymentController::class, 'updatePayment'])
        ->middleware('can:payments.edit');
    Route::any('{method}', [PaymentController::class, 'update'])
        ->middleware('can:payments.edit');
});
