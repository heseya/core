<?php

use App\Enums\SavedAddressType;
use App\Http\Controllers\MetadataController;
use App\Http\Controllers\ProviderController;
use Domain\User\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('app.restrict');

    Route::get('profile', [AuthController::class, 'profile']);
    Route::patch('profile', [AuthController::class, 'updateProfile'])
        ->middleware('can:authenticated');
    Route::patch('profile/roles', [AuthController::class, 'selfUpdateRoles'])
        ->middleware('can:authenticated');
    Route::patch('profile/metadata-personal', [MetadataController::class, 'updateOrCreateLoggedMyPersonal'])
        ->middleware('can:authenticated');
    Route::prefix('profile')
        ->middleware('can:profile.addresses_manage')
        ->group(function (): void {
            Route::post('shipping-addresses', [AuthController::class, 'storeSavedAddress'])
                ->defaults('type', SavedAddressType::SHIPPING);
            Route::patch('shipping-addresses/id:{address}', [AuthController::class, 'updateSavedAddress'])
                ->defaults('type', SavedAddressType::SHIPPING);
            Route::delete('shipping-addresses/id:{address}', [AuthController::class, 'deleteSavedAddress'])
                ->defaults('type', SavedAddressType::SHIPPING);
            Route::post('billing-addresses', [AuthController::class, 'storeSavedAddress'])
                ->defaults('type', SavedAddressType::BILLING);
            Route::patch('billing-addresses/id:{address}', [AuthController::class, 'updateSavedAddress'])
                ->defaults('type', SavedAddressType::BILLING);
            Route::delete('billing-addresses/id:{address}', [AuthController::class, 'deleteSavedAddress'])
                ->defaults('type', SavedAddressType::BILLING);
        });

    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('verify', [AuthController::class, 'verifyEmail']);

    Route::get('check', [AuthController::class, 'checkIdentity'])
        ->middleware('can:auth.check_identity');
    Route::get('check/{identity_token}', [AuthController::class, 'checkIdentity'])
        ->middleware('can:auth.check_identity');
    Route::post('2fa/setup', [AuthController::class, 'setupTFA'])
        ->middleware('can:authenticated');
    Route::post('2fa/confirm', [AuthController::class, 'confirmTFA'])
        ->middleware('can:authenticated');

    Route::post('2fa/remove', [AuthController::class, 'removeTFA'])
        ->middleware('can:authenticated');

    Route::post('2fa/recovery/create', [AuthController::class, 'generateRecoveryCodes'])
        ->middleware('can:authenticated');

    Route::prefix('providers')->group(function (): void {
        Route::get('/', [ProviderController::class, 'getProvidersList']);
        Route::post('merge-account', [ProviderController::class, 'mergeAccount'])
            ->middleware('can:authenticated');
        Route::post('{authProviderKey}/login', [ProviderController::class, 'login'])
            ->middleware('can:auth.register');
        Route::post('{authProviderKey}/redirect', [ProviderController::class, 'redirect']);
        Route::get('{authProviderKey}', [ProviderController::class, 'getProvider']);
        Route::patch('{authProviderKey}', [ProviderController::class, 'update'])
            ->middleware('can:auth.providers.manage');
    });
});

Route::post('login', [AuthController::class, 'login'])
    ->middleware('app.restrict');
Route::post('register', [AuthController::class, 'register'])
    ->middleware(['app.restrict', 'can:auth.register']);
Route::put('users/password', [AuthController::class, 'changePassword'])
    ->middleware(['can:authenticated', 'app.restrict', 'can:auth.password_change']);
