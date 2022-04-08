<?php

use App\Http\Controllers\AuditController;
use Illuminate\Support\Facades\Route;

Route::prefix('audits')->group(function (): void {
    Route::get('{class}/id:{id}', [AuditController::class, 'index'])
        ->middleware('can:audits.show')
        ->whereUuid('id');
});
