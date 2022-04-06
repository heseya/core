<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::any('payments/{method}', [PaymentController::class, 'update'])
    ->middleware('can:payments.edit')
    ->whereAlphaNumeric('method');
