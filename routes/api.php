<?php

Route::post('login', 'AuthController@login');
Route::patch('/user/password', 'AuthController@changePassword')->middleware('auth:api');

Route::prefix('products')->group(function () {
    Route::get(null, 'ProductController@index');
    Route::post(null, 'ProductController@create')->middleware('auth:api');
    Route::get('id:{product:id}', 'ProductController@view')->middleware('auth:api');
    Route::get('{product:slug}', 'ProductController@view');
    Route::patch('id:{product:id}', 'ProductController@update')->middleware('auth:api');
    Route::delete('id:{product:id}', 'ProductController@delete')->middleware('auth:api');
});

Route::prefix('orders')->group(function () {
    Route::get(null, 'OrderController@index')->middleware('auth:api');
    Route::post(null,'OrderController@create');
    Route::post('verify','OrderController@verify');
    Route::get('id:{order:id}', 'OrderController@view')->middleware('auth:api');
    Route::get('{order:code}', 'OrderController@viewPublic');
    Route::get('{order:code}/pay/{method}', 'PaymentController@pay');
});

Route::get('payments/{method}', 'PaymentController@receive');

Route::prefix('pages')->group(function () {
    Route::get(null, 'PageController@index');
    Route::post(null, 'PageController@create')->middleware('auth:api');
    Route::get('id:{page:id}', 'PageController@view')->middleware('auth:api');
    Route::get('{page:slug}', 'PageController@view');
    Route::patch('id:{page:id}', 'PageController@update')->middleware('auth:api');
    Route::delete('id:{page:id}', 'PageController@delete')->middleware('auth:api');
});

Route::prefix('brands')->group(function () {
    Route::get(null, 'BrandController@index');
    Route::post(null, 'BrandController@create')->middleware('auth:api');
    Route::patch('id:{brand:id}', 'BrandController@update')->middleware('auth:api');
    Route::delete('id:{brand:id}', 'BrandController@delete')->middleware('auth:api');
});

Route::prefix('categories')->group(function () {
    Route::get(null, 'CategoryController@index');
    Route::post(null, 'CategoryController@create')->middleware('auth:api');
    Route::patch('id:{category:id}', 'CategoryController@update')->middleware('auth:api');
    Route::delete('id:{category:id}', 'CategoryController@delete')->middleware('auth:api');
});

Route::prefix('shipping-methods')->group(function () {
    Route::get(null, 'ShippingMethodController@index');
    Route::post(null, 'ShippingMethodController@create')->middleware('auth:api');
    Route::patch('id:{shipping_method:id}', 'ShippingMethodController@update')->middleware('auth:api');
    Route::delete('id:{shipping_method:id}', 'ShippingMethodController@delete')->middleware('auth:api');
});

Route::prefix('items')->middleware('auth:api')->group(function () {
    Route::get(null, 'ItemController@index');
    Route::post(null, 'ItemController@create');
    Route::get('id:{item:id}', 'ItemController@view');
    Route::patch('id:{item:id}', 'ItemController@update');
    Route::delete('id:{item:id}', 'ItemController@delete');

    Route::get('id:{item:id}/deposits', 'DepositController@view');
    Route::post('id:{item:id}/deposits', 'DepositController@create');
});

Route::get('deposits', 'DepositController@index')->middleware('auth:api');

Route::post('media', 'MediaController@upload')->middleware('auth:api');

// External
Route::prefix('furgonetka')->group(function () {
    Route::post('webhook', 'External\FurgonetkaController@webhook');
});
