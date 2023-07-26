<?php

use App\Http\Controllers\LanguageController;
use Illuminate\Support\Facades\Route;

Route::prefix('languages')->group(function (): void {
    Route::get('/', [LanguageController::class, 'index']);
    Route::post('/', [LanguageController::class, 'store'])
        ->middleware('permission:languages.add');
    Route::patch('/id:{language:id}', [LanguageController::class, 'update'])
        ->middleware('permission:languages.edit');
    Route::delete('/id:{language:id}', [LanguageController::class, 'destroy'])
        ->middleware('permission:languages.remove');
});
