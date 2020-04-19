<?php

// Store
Route::get('products', 'ProductController@index');
Route::post('products', 'ProductController@create');
Route::get('products/id:{product:id}', 'ProductController@view');
Route::get('products/{product:slug}', 'ProductController@view');
Route::put('products/id:{product:id}', 'ProductController@update');
Route::put('products/{product:slug}', 'ProductController@update');

Route::post('orders','OrderController@create');
Route::get('orders/{order:code}', 'OrderController@view');
Route::get('orders/{order:code}/pay/{method}', 'OrderController@pay');

Route::get('pages', 'PageController@index');
Route::get('pages/id:{page:id}', 'PageController@view');
Route::get('pages/{page:slug}', 'PageController@view');

Route::get('brands', 'BrandController@index');
Route::get('categories', 'CategoryController@index');
Route::get('shipping-methods', 'ShippingMethodController@index');
Route::post('shipping-methods', 'ShippingMethodController@create');
Route::put('shipping-methods/id:{shipping_method:id}', 'ShippingMethodController@update');
Route::delete('shipping-methods/id:{shipping_method:id}', 'ShippingMethodController@delete');

Route::get('items', 'ItemController@index');
Route::post('items', 'ItemController@create');
Route::get('items/id:{item:id}', 'ItemController@view');
Route::put('items/id:{item:id}', 'ItemController@update');
Route::delete('items/id:{item:id}', 'ItemController@delete');

// External
Route::prefix('furgonetka')->group(function () {
    Route::post('webhook', 'External\FurgonetkaController@webhook');
});
