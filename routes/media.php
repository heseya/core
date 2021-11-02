<?php

use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

Route::prefix('media')->group(function (): void {
    Route::post(null, [MediaController::class, 'store'])
        ->middleware('permission:pages.add|pages.edit|products.add|products.edit');
    Route::delete('id:{media:id}', [MediaController::class, 'destroy'])
        ->middleware('permission:pages.add|pages.edit|products.add|products.edit');
});
