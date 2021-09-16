<?php

use App\Http\Controllers\AppController;
use Illuminate\Support\Facades\Route;

Route::prefix('apps')->group(function (): void {
    Route::get(null, [AppController::class, 'index'])
        ->middleware('can:apps.show');
    Route::post(null, [AppController::class, 'store'])
        ->middleware('can:apps.install');
});
