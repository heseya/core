<?php

// Store
Route::get('products', 'ProductController@index');
Route::post('products', 'ProductController@create');
Route::get('products/id:{product}', 'ProductController@view');
Route::get('products/{product:slug}', 'ProductController@view');
Route::put('products/id:{product}', 'ProductController@update');
Route::put('products/{product:slug}', 'ProductController@update');

Route::post('orders','OrderController@create');
Route::get('orders/{order:code}', 'OrderController@view');
Route::get('orders/{order:code}/pay/{method}', 'OrderController@pay');

Route::get('pages', 'PageController@index');
Route::get('pages/id:{page}', 'PageController@view');
Route::get('pages/{page:slug}', 'PageController@view');

Route::get('brands', 'BrandController@index');
Route::get('categories', 'CategoryController@index');

// External
Route::prefix('furgonetka')->group(function () {
    Route::post('webhook', 'External\FurgonetkaController@webhook');
});
