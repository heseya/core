<?php

// prefix /admin
Route::middleware('auth')->group(function () {

    Route::get('orders', 'Admin\OrderController@index');
    Route::get('orders/add', 'Admin\OrderController@addForm');
    Route::get('orders/{order}', 'Admin\OrderController@single');

    Route::get('products', 'Admin\ProductController@index');
    Route::get('products/add', 'Admin\ProductController@addForm');
    Route::post('products/add', 'Admin\ProductController@store');
    Route::get('products/{slug}', 'Admin\ProductController@single');

    Route::get('items', 'Admin\ItemController@index');
    Route::get('items/add', 'Admin\ItemController@addForm');
    Route::post('items/add', 'Admin\ItemController@store');
    Route::get('items/{item}', 'Admin\ItemController@single');
    Route::get('items/{item}/delete', 'Admin\ItemController@delete');

    Route::get('chat', 'Admin\ChatController@index');
    Route::get('chat/{chat}', 'Admin\ChatController@single');

    Route::prefix('settings')->group(function () {

        Route::get('/', 'Admin\SettingsController@settings');

        Route::get('email', 'Admin\SettingsController@email');
        Route::get('email/config', 'Admin\SettingsController@emailConfig');
        Route::post('email/config', 'Admin\SettingsController@emailConfigStore');
        Route::get('email/test', 'Admin\SettingsController@emailTest');

        Route::get('categories', 'Admin\SettingsController@categories');
        Route::get('categories/add', 'Admin\SettingsController@categoryAdd');
        Route::post('categories/add', 'Admin\SettingsController@categoryStore');
        Route::put('categories/add', 'Admin\SettingsController@categoryUpdate');

        Route::get('brands', 'Admin\SettingsController@brands');
        Route::get('brands/add', 'Admin\SettingsController@brandAdd');
        Route::post('brands/add', 'Admin\SettingsController@brandStore');
        Route::put('brands/add', 'Admin\SettingsController@brandUpdate');

        Route::get('accounts', 'Admin\SettingsController@accounts');
        Route::get('accounts/add', 'Admin\SettingsController@accountsAdd');
        Route::post('accounts/add', 'Admin\SettingsController@accountsStore');
        Route::get('info', 'Admin\SettingsController@info');
        Route::get('notifications', 'Admin\SettingsController@notifications');

        Route::prefix('facebook')->group(function () {
            Route::get('/', 'FacebookController@settings');
            Route::get('login', 'FacebookController@login');
            Route::get('callback', 'FacebookController@callback');
            Route::get('unlink', 'FacebookController@unlink');
            Route::get('pages', 'FacebookController@pages');
            Route::get('set-page/{access_token}', 'FacebookController@setPage');
        });
    });

    Route::get('logout', 'Admin\AuthController@logout');
});

Auth::routes(['register' => false, 'logout' => false, 'verify' => false]);

Route::redirect('/', '/admin/orders', 302);
