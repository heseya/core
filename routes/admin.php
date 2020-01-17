<?php

// prefix /admin
Route::get('login', 'Admin\AuthController@showLoginForm')->name('login');
Route::post('login', 'Admin\AuthController@login');
Route::get('logout', 'Admin\AuthController@logout');

Route::middleware('auth')->group(function () {

    Route::get('orders', 'Admin\OrderController@index');
    Route::get('orders/create', 'Admin\OrderController@createForm');
    Route::post('orders/create', 'Admin\OrderController@create');
    Route::get('orders/{order}', 'Admin\OrderController@view');
    Route::get('orders/{order}/update', 'Admin\OrderController@updateForm');
    Route::post('orders/{order}/update', 'Admin\OrderController@update');

    Route::get('products', 'Admin\ProductController@index');
    Route::get('products/create', 'Admin\ProductController@createForm');
    Route::post('products/create', 'Admin\ProductController@create');
    Route::get('products/{product}', 'Admin\ProductController@view');
    Route::get('products/{product}/update', 'Admin\ProductController@updateForm');
    Route::post('products/{product}/update', 'Admin\ProductController@update');
    Route::get('products/{product}/delete', 'Admin\ProductController@delete');

    Route::get('items', 'Admin\ItemController@index');
    Route::get('items/create', 'Admin\ItemController@createForm');
    Route::post('items/create', 'Admin\ItemController@create');
    Route::get('items/{item}', 'Admin\ItemController@view');
    Route::get('items/{item}/update', 'Admin\ItemController@updateForm');
    Route::post('items/{item}/update', 'Admin\ItemController@update');
    Route::get('items/{item}/delete', 'Admin\ItemController@delete');

    Route::get('chat', 'Admin\ChatController@index');
    Route::get('chat/{chat}', 'Admin\ChatController@view');

    Route::prefix('settings')->group(function () {

        Route::get('/', 'Admin\SettingsController@settings');

        Route::get('email', 'Admin\SettingsController@email');
        Route::get('email/config', 'Admin\SettingsController@emailConfig');
        Route::post('email/config', 'Admin\SettingsController@emailConfigStore');
        Route::get('email/test', 'Admin\SettingsController@emailTest');

        Route::get('categories', 'Admin\SettingsController@categories');
        Route::get('categories/create', 'Admin\SettingsController@categoryCreateForm');
        Route::post('categories/create', 'Admin\SettingsController@categoryCreate');
        Route::put('categories/create', 'Admin\SettingsController@categoryUpdate');

        Route::get('brands', 'Admin\SettingsController@brands');
        Route::get('brands/create', 'Admin\SettingsController@brandCreateForm');
        Route::post('brands/create', 'Admin\SettingsController@brandCreate');

        Route::get('accounts', 'Admin\SettingsController@accounts');
        Route::get('accounts/create', 'Admin\SettingsController@accountsCreateForm');
        Route::post('accounts/create', 'Admin\SettingsController@accountsCreate');
        Route::get('info', 'Admin\SettingsController@info');
        Route::get('notifications', 'Admin\SettingsController@notifications');

        Route::get('furgonetka', 'Admin\SettingsController@furgonetka');

        Route::prefix('facebook')->group(function () {
            Route::get('/', 'FacebookController@settings');
            Route::get('login', 'FacebookController@login');
            Route::get('callback', 'FacebookController@callback');
            Route::get('unlink', 'FacebookController@unlink');
            Route::get('pages', 'FacebookController@pages');
            Route::get('set-page/{access_token}', 'FacebookController@setPage');
        });
    });
});

Route::redirect('/', '/admin/orders', 302);
