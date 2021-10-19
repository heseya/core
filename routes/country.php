<?php

use App\Http\Controllers\CountriesController;
use Illuminate\Support\Facades\Route;

Route::get('countries', [CountriesController::class, 'index'])
    ->middleware('can:countries.show');
