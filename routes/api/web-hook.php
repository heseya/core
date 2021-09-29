<?php

use App\Http\Controllers\WebHookController;
use Illuminate\Support\Facades\Route;

Route::prefix('web-hooks')->group(function (): void {
    Route::get(null, [WebHookController::class, 'index'])
        ->middleware('permission:webhooks.show');
    Route::post(null, [WebHookController::class, 'store'])
        ->middleware('permission:webhooks.add');
});
