<?php

// Store
Route::get('products', 'ProductController@index');
Route::post('products', 'ProductController@create');
Route::get('products/id:{product:id}', 'ProductController@view');
Route::get('products/{product:slug}', 'ProductController@view');
Route::patch('products/id:{product:id}', 'ProductController@update');
Route::patch('products/{product:slug}', 'ProductController@update');

Route::get('orders', 'OrderController@index');
Route::post('orders','OrderController@create');
Route::post('orders/verify','OrderController@verify');
Route::get('orders/id:{order:id}', 'OrderController@view');
Route::get('orders/{order:code}', 'OrderController@viewPublic');
Route::get('orders/{order:code}/pay/{method}', 'PayController@pay');

Route::get('pages', 'PageController@index');
Route::post('pages', 'PageController@create');
Route::get('pages/id:{page:id}', 'PageController@view');
Route::get('pages/{page:slug}', 'PageController@view');
Route::patch('pages/id:{page:id}', 'PageController@update');
Route::delete('pages/id:{page:id}', 'PageController@delete');

Route::get('brands', 'BrandController@index');
Route::post('brands', 'BrandController@create');
Route::patch('brands/id:{brand:id}', 'BrandController@update');
Route::delete('brands/id:{brand:id}', 'BrandController@delete');

Route::get('categories', 'CategoryController@index');
Route::post('categories', 'CategoryController@create');
Route::patch('categories/id:{category:id}', 'CategoryController@update');
Route::delete('categories/id:{category:id}', 'CategoryController@delete');

Route::get('shipping-methods', 'ShippingMethodController@index');
Route::post('shipping-methods', 'ShippingMethodController@create');
Route::patch('shipping-methods/id:{shipping_method:id}', 'ShippingMethodController@update');
Route::delete('shipping-methods/id:{shipping_method:id}', 'ShippingMethodController@delete');

Route::get('items', 'ItemController@index');
Route::post('items', 'ItemController@create');
Route::get('items/id:{item:id}', 'ItemController@view');
Route::patch('items/id:{item:id}', 'ItemController@update');
Route::delete('items/id:{item:id}', 'ItemController@delete');

Route::get('deposits', 'DepositController@index');
Route::get('items/id:{item:id}/deposits', 'DepositController@view');
Route::post('items/id:{item:id}/deposits', 'DepositController@create');

Route::post('media', 'MediaController@upload');

// External
Route::prefix('furgonetka')->group(function () {
    Route::post('webhook', 'External\FurgonetkaController@webhook');
});
