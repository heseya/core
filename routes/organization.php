<?php

use Domain\Organization\Controllers\OrganizationController;
use Domain\Organization\Controllers\OrganizationShippingAddressController;
use Illuminate\Support\Facades\Route;

Route::prefix('organizations')->group(function (): void {
    Route::get('', [OrganizationController::class, 'index'])
        ->middleware('can:organizations.show');
    Route::post('', [OrganizationController::class, 'create'])
        ->middleware('can:organizations.add');
    Route::get('id:{organization:id}', [OrganizationController::class, 'show'])
        ->middleware('can:organizations.show_details');
    Route::get('{organization:client_id}', [OrganizationController::class, 'show'])
        ->middleware('can:organizations.show_details');
    Route::patch('id:{organization:id}', [OrganizationController::class, 'update'])
        ->middleware('can:organizations.edit');
    Route::delete('id:{organization:id}', [OrganizationController::class, 'delete'])
        ->middleware('can:organizations.remove');
    Route::prefix('id:{organization:id}/shipping-addresses')->group(function (): void {
        Route::get('', [OrganizationShippingAddressController::class, 'index'])
            ->middleware('can:organizations.edit');
        Route::post('', [OrganizationShippingAddressController::class, 'store'])
            ->middleware('can:organizations.edit');
        Route::patch('id:{delivery_address:id}', [OrganizationShippingAddressController::class, 'update'])
            ->middleware('can:organizations.edit');
        Route::delete('id:{delivery_address:id}', [OrganizationShippingAddressController::class, 'delete'])
            ->middleware('can:organizations.edit');
    });
    Route::post('register', [OrganizationController::class, 'register'])
        ->middleware('can:auth.organization_register');
});

Route::prefix('my/organization')->group(function (): void {
    Route::get('', [OrganizationController::class, 'myOrganization'])
        ->middleware(['can:authenticated', 'app.restrict']);
    Route::patch('', [OrganizationController::class, 'myOrganizationEdit'])
        ->middleware(['can:authenticated', 'app.restrict']);
    Route::get('shipping-addresses', [OrganizationShippingAddressController::class, 'indexMy'])
        ->middleware(['can:authenticated', 'app.restrict']);
    Route::post('shipping-addresses', [OrganizationShippingAddressController::class, 'storeMy'])
        ->middleware(['can:authenticated', 'app.restrict']);
    Route::patch('shipping-addresses/id:{address:id}', [OrganizationShippingAddressController::class, 'updateMy'])
        ->middleware(['can:authenticated', 'app.restrict']);
    Route::delete('shipping-addresses/id:{address:id}', [OrganizationShippingAddressController::class, 'deleteMy'])
        ->middleware(['can:authenticated', 'app.restrict']);
});