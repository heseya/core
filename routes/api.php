<?php

// API routes, nie ma prefixu
Route::get('/', 'Store\StaticController@index');

// Store
Route::get('products', 'Store\ProductController@index');
Route::get('products/{slug}', 'Store\ProductController@single');

Route::get('brands', 'Store\StaticController@brands');
Route::get('categories', 'Store\StaticController@categories');

// External
Route::prefix('furgonetka')->group(function () {
    Route::post('webhook', 'External\FurgonetkaController@webhook');
});
