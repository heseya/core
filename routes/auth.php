<?php

use App\Enums\SavedAddressType;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('app.restrict');

    Route::get('profile', [AuthController::class, 'profile']);
    Route::patch('profile', [AuthController::class, 'updateProfile'])
        ->middleware('can:authenticated');
    Route::prefix('profile')
        ->middleware('can:profile.addresses_manage')
        ->group(function (): void {
            Route::post('delivery-addresses', [AuthController::class, 'storeSavedAddress'])
                ->defaults('type', SavedAddressType::DELIVERY->value);
            Route::patch('delivery-addresses/id:{address}', [AuthController::class, 'updateSavedAddress'])
                ->defaults('type', SavedAddressType::DELIVERY->value);
            Route::delete('delivery-addresses/id:{address}', [AuthController::class, 'deleteSavedAddress'])
                ->defaults('type', SavedAddressType::DELIVERY->value);
            Route::post('invoice-addresses', [AuthController::class, 'storeSavedAddress'])
                ->defaults('type', SavedAddressType::INVOICE->value);
            Route::patch('invoice-addresses/id:{address}', [AuthController::class, 'updateSavedAddress'])
                ->defaults('type', SavedAddressType::INVOICE->value);
            Route::delete('invoice-addresses/id:{address}', [AuthController::class, 'deleteSavedAddress'])
                ->defaults('type', SavedAddressType::INVOICE->value);
        });

    Route::post('refresh', [AuthController::class, 'refresh'])
        ->middleware('can:auth.login');
    Route::get('check', [AuthController::class, 'checkIdentity'])
        ->middleware('can:auth.check_identity');
    Route::get('check/{identity_token}', [AuthController::class, 'checkIdentity'])
        ->middleware('can:auth.check_identity')
        ->where('identity_token', '^([a-zA-Z0-9_=]+)\.([a-zA-Z0-9_=]+)\.([a-zA-Z0-9_\-\+\/=]*)');
    Route::post('2fa/setup', [AuthController::class, 'setupTFA'])
        ->middleware('can:authenticated');
    Route::post('2fa/confirm', [AuthController::class, 'confirmTFA'])
        ->middleware('can:authenticated');

    Route::post('2fa/remove', [AuthController::class, 'removeTFA'])
        ->middleware('can:authenticated');

    Route::post('2fa/recovery/create', [AuthController::class, 'generateRecoveryCodes'])
        ->middleware('can:authenticated');
});

Route::post('login', [AuthController::class, 'login'])
    ->middleware(['app.restrict', 'can:auth.login']);
Route::post('register', [AuthController::class, 'register'])
    ->middleware(['app.restrict', 'can:auth.register']);
Route::put('users/password', [AuthController::class, 'changePassword'])
    ->middleware(['app.restrict', 'can:auth.password_change']);
