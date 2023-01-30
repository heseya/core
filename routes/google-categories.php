<?php

use App\Http\Controllers\GoogleCategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/google-categories/{lang}', [GoogleCategoryController::class, 'index']);
