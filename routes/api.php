<?php

use Illuminate\Http\Request;

Route::prefix('admin')->group(function () {
  Route::get('orders', 'AdminApiController@orders');
  Route::get('products', 'AdminApiController@products');
  Route::get('chats', 'AdminApiController@chats');
});
