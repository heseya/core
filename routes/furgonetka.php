<?php

use App\Http\Controllers\External\FurgonetkaController;
use Illuminate\Support\Facades\Route;

// External
Route::prefix('furgonetka')->group(function (): void {
    Route::post('create-package', [FurgonetkaController::class, 'createPackage'])
        ->middleware('permission:orders.edit|orders.edit.status');
});
