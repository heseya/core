<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('app.restrict');
    Route::get('profile', [AuthController::class, 'profile']);
//    Route::get('login-history', [AuthController::class, 'loginHistory'])
//        ->middleware('can:auth.sessions.show');
//    Route::get('kill-session/id:{id}', [AuthController::class, 'killActiveSession'])
//        ->middleware('can:auth.sessions.revoke');
//    Route::get('kill-all-sessions', [AuthController::class, 'killAllSessions'])
//        ->middleware('can:auth.sessions.revoke');
    Route::post('refresh', [AuthController::class, 'refresh'])
        ->middleware('can:auth.login');
    Route::get('check', [AuthController::class, 'checkIdentity'])
        ->middleware('can:auth.check_identity');
    Route::get('check/{identity_token}', [AuthController::class, 'checkIdentity'])
        ->middleware('can:auth.check_identity');
});

Route::post('login', [AuthController::class, 'login'])
    ->middleware(['app.restrict', 'can:auth.login']);
Route::patch('user/password', [AuthController::class, 'changePassword'])
    ->middleware(['app.restrict', 'can:auth.password_change']);
