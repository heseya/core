<?php

use Domain\ProductAttribute\Controllers\FilterController;
use Illuminate\Support\Facades\Route;

Route::get('filters', [FilterController::class, 'indexBySetsIds']);
