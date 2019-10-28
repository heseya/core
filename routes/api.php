<?php

Route::get('products', 'ApiController@products');
Route::get('products/{product}', 'ApiController@product');

Route::prefix('furgonetka')->group(function () {
    Route::post('webhook', 'FurgonetkaController@webhook');
});

Route::prefix('admin')->group(function () {
    Route::get('orders', 'AdminApiController@orders');
    Route::get('products', 'AdminApiController@products');
    Route::get('chats', 'AdminApiController@chats');
    Route::post('status', 'AdminApiController@changeStatus');
});
