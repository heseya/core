<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MetadataController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(function (): void {
    Route::get('/reset-password/{token?}/{email?}', [AuthController::class, 'showResetPasswordForm'])
        ->middleware(['app.restrict', 'can:auth.password_reset']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware(['app.restrict', 'can:auth.password_reset']);
    Route::put('/save-reset-password', [AuthController::class, 'saveResetPassword'])
        ->middleware(['app.restrict', 'can:auth.password_reset']);
    Route::post('/id:{user:id}/2fa/remove', [AuthController::class, 'removeUsersTFA'])
        ->middleware('permission:users.2fa_remove');

    Route::get(null, [UserController::class, 'index'])
        ->middleware('can:users.show');
    Route::get('id:{user:id}', [UserController::class, 'show'])
        ->middleware('can:users.show_details')
        ->whereUuid('user');
    Route::post(null, [UserController::class, 'store'])
        ->middleware('can:users.add');
    Route::patch('id:{user:id}', [UserController::class, 'update'])
        ->middleware('can:users.edit');
    Route::patch('id:{user:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:users.edit');
    Route::patch('id:{user:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:users.edit');
    Route::patch('id:{user:id}/metadata-personal', [MetadataController::class, 'updateOrCreateUserPersonal'])
        ->middleware('can:users.edit');
    Route::delete('id:{user:id}', [UserController::class, 'destroy'])
        ->middleware('can:users.remove');
});
