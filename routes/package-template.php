<?php

use App\Http\Controllers\PackageTemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('package-templates')->group(function (): void {
    Route::get(null, [PackageTemplateController::class, 'index'])
        ->middleware('can:packages.show');
    Route::post(null, [PackageTemplateController::class, 'store'])
        ->middleware('can:packages.add');
    Route::patch('id:{package:id}', [PackageTemplateController::class, 'update'])
        ->middleware('can:packages.edit');
    Route::delete('id:{package:id}', [PackageTemplateController::class, 'destroy'])
        ->middleware('can:packages.remove');
});
