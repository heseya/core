<?php

// API routes, nie ma prefixu
Route::get('/', 'Store\StaticController@index');

// Store
Route::get('products', 'Store\ProductsController@index');
Route::get('products/{product}', 'Store\ProductsController@view');

Route::get('orders/{order}', 'Store\OrdersController@view');

Route::get('pages', 'Store\PagesController@index');
Route::get('pages/{page}', 'Store\PagesController@view');

Route::get('brands', 'Store\StaticController@brands');
Route::get('categories', 'Store\StaticController@categories');

// External
Route::prefix('furgonetka')->group(function () {
    Route::post('webhook', 'External\FurgonetkaController@webhook');
});
