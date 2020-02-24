<?php

// API routes, nie ma prefixu
Route::get('/', 'Store\StaticController@index');

// Store
Route::get('products', 'Store\ProductController@index');
Route::get('products/{product}', 'Store\ProductController@view');

Route::get('orders/{order}', 'Store\OrderController@view');
Route::get('orders/{order}/pay/{method}', 'Store\OrderController@pay');

Route::get('pages', 'Store\PageController@index');
Route::get('pages/{page}', 'Store\PageController@view');

Route::get('brands', 'Store\StaticController@brands');
Route::get('categories', 'Store\StaticController@categories');

// External
Route::prefix('furgonetka')->group(function () {
    Route::post('webhook', 'External\FurgonetkaController@webhook');
});
