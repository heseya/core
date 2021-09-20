<?php

use App\Http\Controllers\DepositController;
use Illuminate\Support\Facades\Route;

Route::get('deposits', [DepositController::class, 'index'])
    ->middleware('can:deposits.show');
