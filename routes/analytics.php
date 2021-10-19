<?php

use App\Http\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('analytics')->group(function (): void {
    Route::get('payments', [AnalyticsController::class, 'payments'])
        ->middleware('can:analytics.payments');
});
