<?php

Route::get('/', 'HomeController@index');

// Panel admina
Route::prefix('admin')->group(function () {

    Route::middleware('auth')->group(function () {

        Route::get('orders', 'Admin\OrderController@orders');
        Route::get('orders/add', 'Admin\OrderController@ordersAdd');
        Route::get('orders/{order}', 'Admin\OrderController@order');

        Route::get('products', 'Admin\ProductController@products');
        Route::get('products/add', 'Admin\ProductController@productsAdd');
        Route::post('products/add', 'Admin\ProductController@productsStore');
        Route::get('products/{product}', 'Admin\ProductController@product');

        Route::get('chat', 'Admin\ChatController@chats');
        Route::get('chat/{chat}', 'Admin\ChatController@chat');

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

    Auth::routes(['register' => false, 'logout' => false]);
});

Route::redirect('/panel', '/admin', 301);
Route::redirect('/admin', '/admin/orders', 302);
Route::redirect('/home', '/', 301);
