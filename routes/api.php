<?php

// API routes, no prefixu
Route::redirect('/', '/admin', 301);

// Store
Route::get('products', 'ProductController@index');
Route::get('products/{product:slug}', 'ProductController@view');

Route::get('orders/{order:code}', 'OrderController@view');
Route::get('orders/{order:code}/pay/{method}', 'OrderController@pay');

Route::get('pages', 'PageController@index');
Route::get('pages/{page:slug}', 'PageController@view');

Route::get('brands', 'StaticController@brands');
Route::get('categories', 'StaticController@categories');

// External
Route::prefix('furgonetka')->group(function () {
    Route::post('webhook', 'External\FurgonetkaController@webhook');
});
