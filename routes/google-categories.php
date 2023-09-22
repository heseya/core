<?php

use Domain\GoogleCategory\Controllers\GoogleCategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/google-categories/{lang}', [GoogleCategoryController::class, 'index']);
