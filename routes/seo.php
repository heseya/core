<?php

use Domain\Seo\SeoMetadataController;
use Illuminate\Support\Facades\Route;

Route::prefix('seo')->group(function (): void {
    Route::get('/', [SeoMetadataController::class, 'show']);
    Route::patch('/', [SeoMetadataController::class, 'createOrUpdate'])
        ->middleware('permission:seo.edit');
    Route::post('check', [SeoMetadataController::class, 'checkKeywords'])
        ->middleware('permission:seo.edit|pages.edit|products.edit|product_sets.edit');
});
