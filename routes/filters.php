<?php

use Domain\ProductAttribute\Controllers\AttributeController;
use Illuminate\Support\Facades\Route;

Route::get('filters', [AttributeController::class, 'filters']);
