<?php

Route::get('/', 'HomeController@index');

// Panel admina
Route::prefix('admin')->group(function () {

    Route::middleware('auth')->group(function () {

        Route::get('orders', 'AdminController@orders');
        Route::get('orders/add', 'AdminController@ordersAdd');
        Route::get('orders/{order}', 'AdminController@order');

        Route::get('products', 'AdminController@products');
        Route::get('products/add', 'AdminController@productsAdd');
        Route::post('products/add', 'AdminController@productsStore');
        Route::get('products/{product}', 'AdminController@product');

        Route::get('chat', 'ChatController@chats');
        Route::get('chat/{chat}', 'ChatController@chat');

        Route::prefix('settings')->group(function () {

            Route::get('/', 'AdminController@settings');

            Route::get('email', 'AdminController@email');
            Route::get('email/config', 'AdminController@emailConfig');
            Route::post('email/config', 'AdminController@emailConfigStore');
            Route::get('email/test', 'AdminController@emailTest');

            Route::get('categories', 'AdminController@categories');
            Route::get('categories/add', 'AdminController@categoryAdd');
            Route::post('categories/add', 'AdminController@categoryStore');
            Route::put('categories/add', 'AdminController@categoryUpdate');

            Route::get('brands', 'AdminController@brands');
            Route::get('brands/add', 'AdminController@brandAdd');
            Route::post('brands/add', 'AdminController@brandStore');
            Route::put('brands/add', 'AdminController@brandUpdate');

            Route::get('accounts', 'AdminController@accounts');
            Route::get('accounts/add', 'AdminController@accountsAdd');
            Route::post('accounts/add', 'AdminController@accountsStore');
            Route::get('info', 'AdminController@info');
            Route::get('notifications', 'AdminController@notifications');

            Route::prefix('facebook')->group(function () {

                Route::get('/', 'FacebookController@settings');
                Route::get('login', 'FacebookController@login');
                Route::get('callback', 'FacebookController@callback');
                Route::get('unlink', 'FacebookController@unlink');
                Route::get('pages', 'FacebookController@pages');
                Route::get('set-page/{access_token}', 'FacebookController@setPage');
            });
        });

        Route::get('logout', 'Auth\LoginController@logout');
    });

    Auth::routes(['register' => false, 'logout' => false]);
});

Route::redirect('/panel', '/admin', 301);
Route::redirect('/admin', '/admin/orders', 302);
Route::redirect('/home', '/', 301);
