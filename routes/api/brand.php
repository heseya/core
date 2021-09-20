<?php

use App\Http\Controllers\BrandController;
use Illuminate\Support\Facades\Route;

Route::get('brands', [BrandController::class, 'index'])
    ->middleware('can:product_sets.show');
