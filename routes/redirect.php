<?php

use Domain\Redirect\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

Route::prefix('redirects')->group(function (): void {
    Route::get('/', [RedirectController::class, 'index'])
        ->middleware('permission:redirects.show');
    Route::get('id:{redirect:id}', [RedirectController::class, 'show'])
        ->middleware('can:redirects.show');
    Route::post('/', [RedirectController::class, 'store'])
        ->middleware('permission:redirects.add');
    Route::patch('id:{redirect:id}', [RedirectController::class, 'update'])
        ->middleware('can:redirects.edit');
    Route::delete('id:{redirect:id}', [RedirectController::class, 'destroy'])
        ->middleware('can:redirects.remove');
});
