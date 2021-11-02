<?php

use App\Http\Controllers\SeoMetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('seo')->group(function (): void {
    Route::get(null, [SeoMetadataController::class, 'show'])
        ->middleware('permission:seo.show');
    Route::patch(null, [SeoMetadataController::class, 'createOrUpdate'])
        ->middleware('permission:seo.edit');
});
