<?php

use App\Http\Controllers\ConsentController;
use Illuminate\Support\Facades\Route;

Route::prefix('consents')->group(function (): void {
    Route::get('/id:{consent:id}', [ConsentController::class, 'show'])
        ->middleware('can:consents.show_details');
    Route::get('/', [ConsentController::class, 'index'])
        ->middleware('can:consents.show');
    Route::post('/', [ConsentController::class, 'store'])
        ->middleware('can:consents.add');
    Route::patch('/id:{consent:id}', [ConsentController::class, 'update'])
        ->middleware('can:consents.edit');
    Route::delete('/id:{consent:id}', [ConsentController::class, 'destroy'])
        ->middleware('can:consents.remove');
});
