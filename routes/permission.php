<?php

use App\Http\Controllers\PermissionController;
use Illuminate\Support\Facades\Route;

Route::get('permissions', [PermissionController::class, 'index'])
    ->middleware('permission:roles.show_details|roles.add|roles.edit');
