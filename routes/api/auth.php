<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth:api');
    Route::get('login-history', [AuthController::class, 'loginHistory'])
        ->middleware('can:auth.sessions.show');
    Route::get('kill-session/id:{id}', [AuthController::class, 'killActiveSession'])
        ->middleware('can:auth.sessions.revoke');
    Route::get('kill-all-sessions', [AuthController::class, 'killAllSessions'])
        ->middleware('can:auth.sessions.revoke');
    Route::get('profile', [AuthController::class, 'profile']);
});

Route::post('login', [AuthController::class, 'login'])
    ->middleware('can:auth.login');
Route::patch('user/password', [AuthController::class, 'changePassword'])
    ->middleware('can:auth.password_change');
