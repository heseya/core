<?php

use App\Http\Controllers\WebHookController;
use Illuminate\Support\Facades\Route;

Route::prefix('web-hooks')->group(function (): void {
    Route::get(null, [WebHookController::class, 'index'])
        ->middleware('permission:webhooks.show');
    Route::get('id:{web_hook:id}', [WebHookController::class, 'show'])
        ->middleware('permission:webhooks.show_details');
    Route::post(null, [WebHookController::class, 'store'])
        ->middleware('permission:webhooks.add');
    Route::patch('id:{web_hook:id}', [WebHookController::class, 'update'])
        ->middleware('permission:webhooks.edit');
    Route::delete('id:{web_hook:id}', [WebHookController::class, 'destroy'])
        ->middleware('permission:webhooks.remove');
});
