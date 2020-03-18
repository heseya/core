<?php

Route::middleware('guest')->group(function () {
    Route::get('login', 'AuthController@loginForm')->name('login');
    Route::post('login', 'AuthController@login');
});

Route::middleware('auth')->group(function () {


Route::get('logout', 'AuthController@logout')->name('logout');

    // Orders
    Route::prefix('orders')->group(function () {
        Route::group(['middleware' => ['perm:createOrders']], function () {
            Route::get('create', 'OrderController@createForm')->name('orders.create');
            Route::post('create', 'OrderController@create');
        });
        Route::group(['middleware' => ['perm:viewOrders']], function () {
            Route::get('/', 'OrderController@index')->name('orders');
            Route::get('{order}', 'OrderController@view')->name('orders.view');
        });
        Route::group(['middleware' => ['perm:manageOrders']], function () {
            Route::get('{order}/update', 'OrderController@updateForm')->name('orders.update');
            Route::post('{order}/update', 'OrderController@update');
            Route::post('{order}/status', 'OrderController@updateStatus')->name('orders.status');
        });
    });

    // Products
    Route::prefix('products')->group(function () {
        Route::group(['middleware' => ['perm:createProducts']], function () {
            Route::get('create', 'ProductController@createForm')->name('products.create');
            Route::post('create', 'ProductController@create');
        });
        Route::group(['middleware' => ['perm:viewProducts']], function () {
            Route::get('/', 'ProductController@index')->name('products');
            Route::get('{product}', 'ProductController@view')->name('products.view');
        });
        Route::group(['middleware' => ['perm:manageProducts']], function () {
            Route::get('{product}/update', 'ProductController@updateForm')->name('products.update');
            Route::post('{product}/update', 'ProductController@update');
            Route::get('{product}/delete', 'ProductController@delete')->name('products.delete');
        });
    });

    // Items
    Route::prefix('items')->group(function () {
        Route::group(['middleware' => ['perm:createProducts']], function () {
            Route::get('create', 'ItemController@createForm')->name('items.create');
            Route::post('create', 'ItemController@create');
        });
        Route::group(['middleware' => ['perm:viewProducts']], function () {
            Route::get('/', 'ItemController@index')->name('items');
            Route::get('{item}', 'ItemController@view')->name('items.view');
        });
        Route::group(['middleware' => ['perm:manageProducts']], function () {
            Route::get('{item}/update', 'ItemController@updateForm')->name('items.update');
            Route::post('{item}/update', 'ItemController@update');
            Route::get('{item}/delete', 'ItemController@delete')->name('items.delete');
        });
    });

    // Chat
    Route::prefix('chats')->group(function () {
        Route::group(['middleware' => ['perm:viewChats']], function () {
            Route::get('/', 'ChatController@index')->name('chats');
            Route::get('sync', 'ChatController@sync')->name('chats.sync');
            Route::get('{chat}', 'ChatController@view')->name('chats.view');
        });
        Route::group(['middleware' => ['perm:replyChats']], function () {
            Route::post('{chat}', 'ChatController@send')->name('chats.send');
        });
    });

    // Items
    Route::prefix('pages')->group(function () {
        Route::group(['middleware' => ['perm:createPages']], function () {
            Route::get('create', 'PageController@createForm')->name('pages.create');
            Route::post('create', 'PageController@create');
        });
        Route::group(['middleware' => ['perm:viewPages']], function () {
            Route::get('/', 'PageController@index')->name('pages');
            Route::get('{page}', 'PageController@view')->name('pages.view');
        });
        Route::group(['middleware' => ['perm:managePages']], function () {
            Route::get('{page}/update', 'PageController@updateForm')->name('pages.update');
            Route::post('{page}/update', 'PageController@update');
            Route::get('{page}/delete', 'PageController@delete')->name('pages.delete');
        });
    });

    // Settings
    Route::prefix('settings')->group(function () {

        Route::get('/', 'SettingsController@settings')->name('settings');

        Route::group(['middleware' => ['perm:manageStore']], function () {
            Route::get('email', 'SettingsController@email')->name('email');
            Route::get('email/config', 'SettingsController@emailConfig');
            Route::post('email/config', 'SettingsController@emailConfigStore');
            Route::get('email/test', 'SettingsController@emailTest')->name('email.test');

            Route::get('categories', 'SettingsController@categories')->name('categories');
            Route::get('categories/create', 'SettingsController@categoryCreateForm')->name('categories.create');
            Route::post('categories/create', 'SettingsController@categoryCreate');

            Route::get('categories/{category}/update', 'SettingsController@categoryUpdateForm')->name('categories.update');
            Route::post('categories/{category}/update', 'SettingsController@categoryUpdate');
            Route::get('categories/{category}/delete', 'SettingsController@categoryDelete')->name('categories.delete');

            Route::get('brands', 'SettingsController@brands')->name('brands');
            Route::get('brands/create', 'SettingsController@brandCreateForm')->name('brands.create');
            Route::post('brands/create', 'SettingsController@brandCreate');

            Route::get('brands/{brand}/update', 'SettingsController@brandUpdateForm')->name('brands.update');
            Route::post('brands/{brand}/update', 'SettingsController@brandUpdate');
            Route::get('brands/{brand}/delete', 'SettingsController@brandDelete')->name('brands.delete');

            Route::get('furgonetka', 'SettingsController@furgonetka')->name('furgonetka');
        });

        Route::prefix('users')->group(function () {
            Route::get('/', 'UserController@index')->name('users');
            Route::get('create', 'UserController@createForm')->name('users.create');
            Route::post('create', 'UserController@create');
            Route::get('{user}', 'UserController@view')->name('users.view');
            Route::post('{user}/rbac', 'UserController@rbac')->name('users.rbac');
        });

        Route::get('info', 'SettingsController@info')->name('info');
        Route::get('docs', 'SettingsController@docs')->name('docs');

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
