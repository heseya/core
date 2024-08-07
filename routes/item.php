<?php

use App\Http\Controllers\DepositController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('items')->group(function (): void {
    Route::get('/', [ItemController::class, 'index'])
        ->middleware('can:items.show');
    Route::post('/', [ItemController::class, 'store'])
        ->middleware('can:items.add');
    Route::get('id:{item:id}', [ItemController::class, 'show'])
        ->middleware('can:items.show_details');
    Route::patch('id:{item:id}', [ItemController::class, 'update'])
        ->middleware('can:items.edit');
    Route::patch('id:{item:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:items.edit');
    Route::patch('id:{item:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:items.edit');
    Route::delete('id:{item:id}', [ItemController::class, 'destroy'])
        ->middleware('can:items.remove');

    Route::get('id:{item:id}/deposits', [DepositController::class, 'show'])
        ->middleware('can:deposits.show');
    Route::post('id:{item:id}/deposits', [DepositController::class, 'store'])
        ->middleware('can:deposits.add');
});
