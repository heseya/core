<?php

// prefix /admin
Route::get('login', 'AuthController@showLoginForm')->name('login');
Route::post('login', 'AuthController@login');
Route::get('logout', 'AuthController@logout');

Route::middleware('auth')->group(function () {

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', 'OrderController@index');
        Route::get('create', 'OrderController@createForm');
        Route::post('create', 'OrderController@create');
        Route::get('{order}', 'OrderController@view');
        Route::get('{order}/update', 'OrderController@updateForm');
        Route::post('{order}/update', 'OrderController@update');
        Route::post('{order}/status', 'OrderController@updateStatus');
    });

    // Products
    Route::prefix('products')->group(function () {
        Route::get('/', 'ProductController@index');
        Route::get('create', 'ProductController@createForm');
        Route::post('create', 'ProductController@create');
        Route::get('{product}', 'ProductController@view');
        Route::get('{product}/update', 'ProductController@updateForm');
        Route::post('{product}/update', 'ProductController@update');
        Route::get('{product}/delete', 'ProductController@delete');
    });

    // Items
    Route::prefix('items')->group(function () {
        Route::get('/', 'ItemController@index');
        Route::get('create', 'ItemController@createForm');
        Route::post('create', 'ItemController@create');
        Route::get('{item}', 'ItemController@view');
        Route::get('{item}/update', 'ItemController@updateForm');
        Route::post('{item}/update', 'ItemController@update');
        Route::get('{item}/delete', 'ItemController@delete');
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
            Route::get('/', 'PageController@index');
            Route::get('create', 'PageController@createForm');
            Route::post('create', 'PageController@create');
            Route::get('{page}', 'PageController@view');
            Route::get('{page}/update', 'PageController@updateForm');
            Route::post('{page}/update', 'PageController@update');
            Route::get('{page}/delete', 'PageController@delete');
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

        Route::prefix('users')->group(function () {
            Route::get('/', 'UserController@index');
            Route::get('create', 'UserController@createForm');
            Route::post('create', 'UserController@create');
            Route::get('{user}', 'UserController@view');
            Route::post('{user}/rbac', 'UserController@rbac');
        });

        Route::get('info', 'SettingsController@info');
        Route::get('docs', 'SettingsController@docs');
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
