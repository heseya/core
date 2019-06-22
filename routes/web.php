<?php

Route::get('/', function () {
  return view('welcome');
});

Route::get('zamawiam', 'Controller@clientInfo');


// Panel admina
Route::prefix('admin')->group(function () {

  Route::middleware('auth')->group(function () {
    Route::get('orders', 'AdminController@orders');
    Route::get('products', 'AdminController@products');
    Route::get('chat', 'AdminController@chat');
    Route::get('chat/{id}', 'AdminController@chatSingle');

    Route::get('logout', 'Auth\LoginController@logout');
  });

  Auth::routes(['register' => false, 'logout' => false]);
});

Route::redirect('/panel', '/admin', 301);

Route::get('/home', 'HomeController@index')->name('home');
