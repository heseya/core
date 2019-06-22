<?php

use Illuminate\Http\Request;

Route::prefix('admin')->group(function () {
  Route::get('orders', 'AdminApiController@orders');
});
