<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/google-categories/{lang}', [CategoryController::class, 'index']);
