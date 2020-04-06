<?php

// API routes, nie ma prefixu
Route::get('/', 'StaticController@index');

// Store
Route::get('products', 'ProductController@index');
Route::get('products/{product}', 'ProductController@view');

Route::post('orders/create','OrderController@create');
Route::get('orders/{order}', 'OrderController@view');
Route::get('orders/{order}/pay/{method}', 'OrderController@pay');

Route::get('pages', 'PageController@index');
Route::get('pages/{page}', 'PageController@view');

Route::get('brands', 'StaticController@brands');
Route::get('categories', 'StaticController@categories');

// External
Route::prefix('furgonetka')->group(function () {
    Route::post('webhook', 'External\FurgonetkaController@webhook');
});
