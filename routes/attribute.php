<?php

use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeOptionController;
use App\Http\Controllers\MetadataController;
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
    Route::get('id:{attribute:id}/options', [AttributeOptionController::class, 'index'])
        ->middleware('permission:attributes.show');
    Route::post('id:{attribute:id}/options', [AttributeOptionController::class, 'store'])
        ->middleware('permission:attributes.edit');
    Route::patch('id:{attribute:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:attributes.edit');
    Route::patch('id:{attribute:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:attributes.edit');
    Route::patch('id:{attribute:id}/options/id:{option:id}', [AttributeOptionController::class, 'update'])
        ->middleware('permission:attributes.edit');
    Route::delete('id:{attribute:id}/options/id:{option:id}', [AttributeOptionController::class, 'destroy'])
        ->middleware('permission:attributes.edit');
    Route::patch('id:{attribute:id}/options/id:{option:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:attributes.edit');
    Route::patch(
        'id:{attribute:id}/options/id:{option:id}/metadata-private',
        [MetadataController::class, 'updateOrCreate']
    )->middleware('can:attributes.edit');
});
