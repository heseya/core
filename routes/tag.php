<?php

use Domain\Tag\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::prefix('tags')->group(function (): void {
    Route::get('/', [TagController::class, 'index'])
        ->middleware('permission:tags.show|products.add|products.edit');
    Route::post('/', [TagController::class, 'store'])
        ->middleware('permission:tags.add|products.add|products.edit');
    Route::patch('id:{tag:id}', [TagController::class, 'update'])
        ->middleware('can:tags.edit');
    Route::delete('id:{tag:id}', [TagController::class, 'destroy'])
        ->middleware('can:tags.remove');
});
