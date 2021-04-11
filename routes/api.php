<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('login', 'AuthController@login');
Route::patch('user/password', 'AuthController@changePassword')->middleware('auth:api');

Route::prefix('products')->group(function () {
    Route::get(null, 'ProductController@index');
    Route::post(null, 'ProductController@store')->middleware('auth:api');
    Route::get('id:{product:id}', 'ProductController@show')->middleware('auth:api');
    Route::get('{product:slug}', 'ProductController@show');
    Route::patch('id:{product:id}', 'ProductController@update')->middleware('auth:api');
    Route::delete('id:{product:id}', 'ProductController@destroy')->middleware('auth:api');
});

Route::prefix('orders')->group(function () {
    Route::get(null, 'OrderController@index')->middleware('auth:api');
    Route::post(null,'OrderController@store');
    Route::post('verify','OrderController@verify');
    Route::get('id:{order:id}', 'OrderController@show')->middleware('auth:api');
    Route::post('id:{order:id}/status', 'OrderController@updateStatus')->middleware('auth:api');
    Route::get('{order:code}', 'OrderController@showPublic');
    Route::post('{order:code}/pay/{method}', 'PaymentController@store');
});

Route::post('payments/{method}', 'PaymentController@update');

Route::prefix('pages')->group(function () {
    Route::get(null, 'PageController@index');
    Route::post(null, 'PageController@store')->middleware('auth:api');
    Route::get('id:{page:id}', 'PageController@show')->middleware('auth:api');
    Route::get('{page:slug}', 'PageController@show');
    Route::patch('id:{page:id}', 'PageController@update')->middleware('auth:api');
    Route::delete('id:{page:id}', 'PageController@destroy')->middleware('auth:api');
});

Route::prefix('brands')->group(function () {
    Route::get(null, 'BrandController@index');
    Route::post(null, 'BrandController@store')->middleware('auth:api');
    Route::patch('id:{brand:id}', 'BrandController@update')->middleware('auth:api');
    Route::delete('id:{brand:id}', 'BrandController@destroy')->middleware('auth:api');
});

Route::prefix('categories')->group(function () {
    Route::get(null, 'CategoryController@index');
    Route::post(null, 'CategoryController@store')->middleware('auth:api');
    Route::patch('id:{category:id}', 'CategoryController@update')->middleware('auth:api');
    Route::delete('id:{category:id}', 'CategoryController@destroy')->middleware('auth:api');
});

Route::prefix('shipping-methods')->group(function () {
    Route::get(null, 'ShippingMethodController@index');
    Route::post(null, 'ShippingMethodController@store')->middleware('auth:api');
    Route::patch('id:{shipping_method:id}', 'ShippingMethodController@update')->middleware('auth:api');
    Route::delete('id:{shipping_method:id}', 'ShippingMethodController@destroy')->middleware('auth:api');
});

Route::prefix('payment-methods')->group(function () {
    Route::get(null, 'PaymentMethodController@index');
    Route::post(null, 'PaymentMethodController@store')->middleware('auth:api');
    Route::patch('id:{payment_method:id}', 'PaymentMethodController@update')->middleware('auth:api');
    Route::delete('id:{payment_method:id}', 'PaymentMethodController@destroy')->middleware('auth:api');
});

Route::prefix('settings')->group(function () {
    Route::get(null, 'SettingController@index');
    Route::get('{setting:name}', 'SettingController@show');
    Route::post(null, 'SettingController@store')->middleware('auth:api');
    Route::patch('{setting:name}', 'SettingController@update')->middleware('auth:api');
    Route::delete('{setting:name}', 'SettingController@destroy')->middleware('auth:api');
});

Route::prefix('package-templates')->middleware('auth:api')->group(function () {
    Route::get(null, 'PackageTemplateController@index');
    Route::post(null, 'PackageTemplateController@store');
    Route::patch('id:{package:id}', 'PackageTemplateController@update');
    Route::delete('id:{package:id}', 'PackageTemplateController@destroy');
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('items')->group(function () {
        Route::get(null, 'ItemController@index');
        Route::post(null, 'ItemController@store');
        Route::get('id:{item:id}', 'ItemController@show');
        Route::patch('id:{item:id}', 'ItemController@update');
        Route::delete('id:{item:id}', 'ItemController@destroy');

        Route::get('id:{item:id}/deposits', 'DepositController@show');
        Route::post('id:{item:id}/deposits', 'DepositController@store');
    });

    Route::prefix('statuses')->group(function () {
        Route::get(null, 'StatusController@index');
        Route::post(null, 'StatusController@store');
        Route::patch('id:{status:id}', 'StatusController@update');
        Route::delete('id:{status:id}', 'StatusController@destroy');
    });

    Route::get('deposits', 'DepositController@index');

    Route::post('media', 'MediaController@store');

    Route::prefix('schemas')->group(function () {
        Route::get(null, 'SchemaController@index');
        Route::post(null, 'SchemaController@store');
        Route::get('id:{schema:id}', 'SchemaController@show');
        Route::patch('id:{schema:id}', 'SchemaController@update');
        Route::delete('id:{schema:id}', 'SchemaController@destroy');
        Route::post('id:{schema:id}/attach/id:{product:id}', 'SchemaController@attach');
        Route::post('id:{schema:id}/detach/id:{product:id}', 'SchemaController@detach');
    });

    Route::prefix('options')->group(function () {
        Route::post(null, 'OptionController@store');
        Route::get('id:{option:id}', 'OptionController@show');
        Route::patch('id:{option:id}', 'OptionController@update');
        Route::delete('id:{option:id}', 'OptionController@destroy');
    });

    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('login-history', [AuthController::class, 'loginHistory']);
    });

    Route::prefix('apps')->group(function () {
        Route::get(null, [AppController::class, 'index']);
    });
});

// External
Route::prefix('furgonetka')->group(function () {
    Route::post('webhook', 'External\FurgonetkaController@webhook');
    Route::post('create-package', 'External\FurgonetkaController@createPackage')->middleware('auth:api');
});
