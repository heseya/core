<?php

use App\Http\Controllers\MetadataController;
use App\Http\Controllers\PackageTemplateController;
use Illuminate\Support\Facades\Route;

Route::prefix('package-templates')->group(function (): void {
    Route::get('/', [PackageTemplateController::class, 'index'])
        ->middleware('can:packages.show');
    Route::post('/', [PackageTemplateController::class, 'store'])
        ->middleware('can:packages.add');
    Route::patch('id:{package:id}', [PackageTemplateController::class, 'update'])
        ->middleware('can:packages.edit');
    Route::patch('id:{package:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:packages.edit');
    Route::patch('id:{package:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:packages.edit');
    Route::delete('id:{package:id}', [PackageTemplateController::class, 'destroy'])
        ->middleware('can:packages.remove');
});
