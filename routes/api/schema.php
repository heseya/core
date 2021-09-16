<?php

use App\Http\Controllers\SchemaController;
use Illuminate\Support\Facades\Route;

Route::prefix('schemas')->group(function (): void {
    Route::get(null, [SchemaController::class, 'index'])
        ->middleware('permission:products.add|products.edit');
    Route::post(null, [SchemaController::class, 'store'])
        ->middleware('permission:products.add|products.edit');
    Route::get('id:{schema:id}', [SchemaController::class, 'show'])
        ->middleware('permission:products.add|products.edit');
    Route::patch('id:{schema:id}', [SchemaController::class, 'update'])
        ->middleware('permission:products.add|products.edit');
    Route::delete('id:{schema:id}', [SchemaController::class, 'destroy'])
        ->middleware('can:schemas.remove');
    Route::post('id:{schema:id}/attach/id:{product:id}', [SchemaController::class, 'attach'])
        ->middleware('can:products.edit');
    Route::post('id:{schema:id}/detach/id:{product:id}', [SchemaController::class, 'detach'])
        ->middleware('can:products.edit');
});
