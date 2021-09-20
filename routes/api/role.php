<?php

use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('roles')->middleware('auth:api')->group(function (): void {
    Route::get(null, [RoleController::class, 'index'])
        ->middleware('can:roles.show');
    Route::post(null, [RoleController::class, 'store'])
        ->middleware('can:roles.add');
    Route::get('id:{role:id}', [RoleController::class, 'show'])
        ->middleware('can:roles.show_details');
    Route::patch('id:{role:id}', [RoleController::class, 'update'])
        ->middleware('can:roles.edit');
    Route::delete('id:{role:id}', [RoleController::class, 'destroy'])
        ->middleware('can:roles.remove');
});