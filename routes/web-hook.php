<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\WebHookController;
use App\Http\Controllers\WebHookLogController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::prefix('webhooks')->group(function (): void {
    Route::get('/', [WebHookController::class, 'index'])
        ->middleware('permission:webhooks.show');
    Route::get('id:{web_hook:id}', [WebHookController::class, 'show'])
        ->middleware('permission:webhooks.show_details');
    Route::post('/', [WebHookController::class, 'store'])
        ->middleware('permission:webhooks.add');
    Route::patch('id:{web_hook:id}', [WebHookController::class, 'update'])
        ->middleware('permission:webhooks.edit');
    Route::delete('id:{web_hook:id}', [WebHookController::class, 'destroy'])
        ->middleware('permission:webhooks.remove');

    Route::get('events', [EventController::class, 'index'])
        ->middleware('permission:events.show');

    Route::get('logs', [WebHookLogController::class, 'index'])
        ->middleware('permission:webhooks.show_details');

    Route::post('/test', function () {
        Log::info('webhook test endpoint');
        sleep(5);
    });

    Route::get('/info', [WebHookController::class, 'info']);
});
