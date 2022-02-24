<?php

use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeOptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('attributes')->group(function (): void {
    Route::get(null, [AttributeController::class, 'index'])
        ->middleware('permission:attributes.show');
    Route::post(null, [AttributeController::class, 'store'])
        ->middleware('permission:attributes.add');
    Route::get('id:{attribute:id}', [AttributeController::class, 'show'])
        ->middleware('permission:attributes.show');
    Route::patch('id:{attribute:id}', [AttributeController::class, 'update'])
        ->middleware('permission:attributes.edit');
    Route::delete('id:{attribute:id}', [AttributeController::class, 'destroy'])
        ->middleware('can:attributes.remove');
    Route::post('id:{attribute:id}/options', [AttributeOptionController::class, 'store'])
        ->middleware('permission:attributes.edit');
});

