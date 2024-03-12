<?php

use Domain\Currency\CurrencyController;
use Illuminate\Support\Facades\Route;

Route::get('currencies', [CurrencyController::class, 'index']);
