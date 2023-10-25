<?php

use Domain\Organization\Controllers\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::prefix('organizations')->group(function (): void {
    Route::get('/', [OrganizationController::class, 'index'])
        ->middleware('can:organizations.show');
    Route::post('/', [OrganizationController::class, 'create'])
        ->middleware('can:organizations.add');
    Route::get('id:{organization:id}', [OrganizationController::class, 'show'])
        ->middleware('can:organizations.show_details');
    Route::patch('id:{organization:id}', [OrganizationController::class, 'update'])
        ->middleware('can:organizations.edit');
    Route::delete('/id:{organization:id}', [OrganizationController::class, 'delete'])
        ->middleware('can:organizations.remove');
    Route::post('/id:{organization:id}/accept', [OrganizationController::class, 'accept'])
        ->middleware('can:organizations.verify');
    Route::post('/id:{organization:id}/reject', [OrganizationController::class, 'reject'])
        ->middleware('can:organizations.verify');
});
