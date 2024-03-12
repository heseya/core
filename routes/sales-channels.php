<?php

use Domain\SalesChannel\Controllers\SalesChannelController;
use Illuminate\Support\Facades\Route;

Route::prefix('sales-channels')->group(function (): void {
    Route::get('/', [SalesChannelController::class, 'index']);
    Route::post('/', [SalesChannelController::class, 'store'])
        ->middleware('can:sales_channels.add');

    Route::get('/id:{sales_channel:id}', [SalesChannelController::class, 'show'])
        ->middleware('published:sales_channel');
    Route::patch('/id:{id}', [SalesChannelController::class, 'update'])
        ->middleware('can:sales_channels.edit');
    Route::delete('/id:{id}', [SalesChannelController::class, 'destroy'])
        ->middleware('can:sales_channels.remove');
});
