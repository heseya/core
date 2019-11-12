<?php

Route::get('products', 'ApiController@products');
Route::get('products/{product}', 'ApiController@product');

// Admin
Route::prefix('admin')->group(function () {
    Route::post('status', 'Admin\ApiController@changeStatus');
    Route::post('upload', 'Admin\ApiController@upload');
});

// 3th Party
Route::prefix('furgonetka')->group(function () {
    Route::post('webhook', '3thParty\FurgonetkaController@webhook');
});
