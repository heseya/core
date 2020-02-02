<?php

// prefix /admin
Route::get('login', 'AuthController@showLoginForm')->name('login');
Route::post('login', 'AuthController@login');
Route::get('logout', 'AuthController@logout');

Route::middleware('auth')->group(function () {

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', 'OrdersController@index');
        Route::get('create', 'OrdersController@createForm');
        Route::post('create', 'OrdersController@create');
        Route::get('{order}', 'OrdersController@view');
        Route::get('{order}/update', 'OrdersController@updateForm');
        Route::post('{order}/update', 'OrdersController@update');
        Route::post('{order}/status', 'OrdersController@updateStatus');
    });

    // Products
    Route::prefix('products')->group(function () {
        Route::get('/', 'ProductsController@index');
        Route::get('create', 'ProductsController@createForm');
        Route::post('create', 'ProductsController@create');
        Route::get('{product}', 'ProductsController@view');
        Route::get('{product}/update', 'ProductsController@updateForm');
        Route::post('{product}/update', 'ProductsController@update');
        Route::get('{product}/delete', 'ProductsController@delete');
    });

    // Items
    Route::prefix('items')->group(function () {
        Route::get('/', 'ItemsController@index');
        Route::get('create', 'ItemsController@createForm');
        Route::post('create', 'ItemsController@create');
        Route::get('{item}', 'ItemsController@view');
        Route::get('{item}/update', 'ItemsController@updateForm');
        Route::post('{item}/update', 'ItemsController@update');
        Route::get('{item}/delete', 'ItemsController@delete');
    });

    // Chat
    Route::prefix('chat')->group(function () {
        Route::get('/', 'ChatController@index');
        Route::get('{chat}', 'ChatController@view');
        Route::post('{chat}', 'ChatController@send');
    });

    Route::middleware('lang')->group(function () {
        // Items
        Route::prefix('pages')->group(function () {
            Route::get('/', 'PagesController@index');
            Route::get('create', 'PagesController@createForm');
            Route::post('create', 'PagesController@create');
            Route::get('{page}', 'PagesController@view');
            Route::get('{page}/update', 'PagesController@updateForm');
            Route::post('{page}/update', 'PagesController@update');
            Route::get('{page}/delete', 'PagesController@delete');
        });
    });

    // Settings
    Route::prefix('settings')->group(function () {

        Route::get('/', 'SettingsController@settings');

        Route::get('email', 'SettingsController@email');
        Route::get('email/config', 'SettingsController@emailConfig');
        Route::post('email/config', 'SettingsController@emailConfigStore');
        Route::get('email/test', 'SettingsController@emailTest');

        Route::get('categories', 'SettingsController@categories');
        Route::get('categories/create', 'SettingsController@categoryCreateForm');
        Route::post('categories/create', 'SettingsController@categoryCreate');
        Route::put('categories/create', 'SettingsController@categoryUpdate');

        Route::get('brands', 'SettingsController@brands');
        Route::get('brands/create', 'SettingsController@brandCreateForm');
        Route::post('brands/create', 'SettingsController@brandCreate');

        Route::get('accounts', 'SettingsController@accounts');
        Route::get('accounts/create', 'SettingsController@accountsCreateForm');
        Route::post('accounts/create', 'SettingsController@accountsCreate');
        Route::get('info', 'SettingsController@info');
        Route::get('notifications', 'SettingsController@notifications');

        Route::get('furgonetka', 'SettingsController@furgonetka');

        Route::prefix('facebook')->group(function () {
            Route::get('/', 'FacebookController@settings');
            Route::get('login', 'FacebookController@login');
            Route::get('callback', 'FacebookController@callback');
            Route::get('unlink', 'FacebookController@unlink');
            Route::get('pages', 'FacebookController@pages');
            Route::get('set-page/{access_token}', 'FacebookController@setPage');
        });
    });

    // Other
    Route::post('media/photo', 'MediaController@uploadPhoto');
});

Route::redirect('/', '/admin/orders', 302);
