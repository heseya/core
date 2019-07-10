<?php

Route::get('/', 'HomeController@index');

// Panel admina
Route::prefix('admin')->group(function () {

  Route::middleware('auth')->group(function () {

    Route::get('orders', 'AdminController@orders');
    Route::get('orders/{order}', 'AdminController@order');
    Route::get('products', 'AdminController@products');
    Route::get('chat', 'FacebookController@chats');
    Route::get('chat/{id}', 'FacebookController@chat');

    Route::prefix('settings')->group(function () {

      Route::get('', 'AdminController@settings');
      Route::get('email', 'AdminController@email');
      Route::get('email/config', 'AdminController@emailConfig');
      Route::get('info', 'AdminController@info');
      Route::get('notifications', 'AdminController@notifications');

      Route::prefix('facebook')->group(function () {

        Route::get('', 'FacebookController@settings');
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
