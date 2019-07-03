<?php

Route::get('/', 'HomeController@index');

// Panel admina
Route::prefix('admin')->group(function () {

  Route::middleware('auth')->group(function () {

    Route::get('orders', 'AdminController@orders');
    Route::get('products', 'AdminController@products');
    Route::get('chat', 'FacebookController@chats');
    Route::get('chat/{id}', 'FacebookController@chat');

    Route::get('settings', 'AdminController@settings');
    Route::get('info', 'AdminController@info');

    Route::get('facebook', 'FacebookController@settings');
    Route::get('facebook/login', 'FacebookController@login');
    Route::get('facebook/callback', 'FacebookController@callback');
    Route::get('facebook/unlink', 'FacebookController@unlink');
    Route::get('facebook/pages', 'FacebookController@pages');
    Route::get('facebook/set-page/{access_token}', 'FacebookController@setPage');

    Route::get('logout', 'Auth\LoginController@logout');
  });

  Auth::routes(['register' => false, 'logout' => false]);
});

Route::redirect('/panel', '/admin', 301);
Route::redirect('/admin', '/admin/orders', 302);
Route::redirect('/home', '/', 301);
