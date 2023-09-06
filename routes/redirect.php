<?php

use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::prefix('redirects')->group(function (): void {
    Route::get('/', [RedirectController::class, 'index'])
        ->middleware('permission:redirects.show|redirects.add|redirects.edit');
    Route::post('/', [RedirectController::class, 'store'])
        ->middleware('permission:redirects.add|redirects.add|redirects.edit');
    Route::patch('id:{redirect:id}', [RedirectController::class, 'update'])
        ->middleware('can:redirects.edit');
    Route::delete('id:{redirect:id}', [RedirectController::class, 'destroy'])
        ->middleware('can:redirects.remove');
});
