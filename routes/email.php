<?php

use Domain\Email\Controllers\EmailController;
use Illuminate\Support\Facades\Route;

Route::prefix('email')->group(function (): void {
    Route::post('', [EmailController::class, 'sendEmail'])
        ->middleware('can:email.send');
});
