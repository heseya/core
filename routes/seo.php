<?php

use App\Http\Controllers\SeoMetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('seo')->group(function (): void {
    Route::get(null, [SeoMetadataController::class, 'show']);
    Route::patch(null, [SeoMetadataController::class, 'createOrUpdate'])
        ->middleware('permission:seo.edit');
    Route::post('check', [SeoMetadataController::class, 'checkKeywords'])
        ->middleware('permission:seo.edit|pages.edit|products.edit|product_sets.edit');
});
