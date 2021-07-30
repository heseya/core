<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CountriesController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\ProductSetController;
use App\Http\Controllers\ShippingMethodController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(function (): void {
    Route::get('/reset-password/{token?}/{email?}', 'AuthController@showResetPasswordForm');
    Route::post('/reset-password', 'AuthController@resetPassword');
    Route::patch('/save-reset-password', 'AuthController@saveResetPassword');

    //TODO temporarily without authorization until it is fully created ...
    Route::prefix('managements')->group(function (): void {
        Route::get(null, 'UserManagementController@index');
        Route::get('id:{user:id}', 'UserManagementController@show');
        Route::post(null, 'UserManagementController@store');
        Route::patch('id:{user:id}', 'UserManagementController@update');
        Route::delete('id:{user:id}', 'UserManagementController@destroy');
    });
});
Route::post('login', 'AuthController@login');
Route::patch('user/password', 'AuthController@changePassword')->middleware('auth:api');

Route::prefix('products')->group(function (): void {
    Route::get(null, 'ProductController@index');
    Route::post(null, 'ProductController@store')->middleware('auth:api');
    Route::get('id:{product:id}', 'ProductController@show')->middleware('auth:api');
    Route::get('{product:slug}', 'ProductController@show');
    Route::patch('id:{product:id}', 'ProductController@update')->middleware('auth:api');
    Route::delete('id:{product:id}', 'ProductController@destroy')->middleware('auth:api');
});

Route::prefix('orders')->group(function (): void {
    Route::get(null, 'OrderController@index')->middleware('auth:api');
    Route::post(null, 'OrderController@store');
    Route::post('sync', 'OrderController@sync')->middleware('auth:api');
    Route::post('verify', 'OrderController@verify');
    Route::get('id:{order:id}', 'OrderController@show')->middleware('auth:api');
    Route::post('id:{order:id}/status', 'OrderController@updateStatus')->middleware('auth:api');
    Route::patch('id:{order:id}', 'OrderController@update')->middleware('auth:api');
    Route::get('{order:code}', 'OrderController@showPublic');
    Route::post('{order:code}/pay/{method}', 'PaymentController@store');
});

Route::any('payments/{method}', 'PaymentController@update');

Route::prefix('pages')->group(function (): void {
    Route::get(null, 'PageController@index');
    Route::post(null, 'PageController@store')->middleware('auth:api');
    Route::get('id:{page:id}', 'PageController@show')->middleware('auth:api');
    Route::get('{page:slug}', 'PageController@show');
    Route::patch('id:{page:id}', 'PageController@update')->middleware('auth:api');
    Route::delete('id:{page:id}', 'PageController@destroy')->middleware('auth:api');
    Route::post('order', 'PageController@reorder')->middleware('auth:api');
});

Route::get('brands', [BrandController::class, 'index']);
Route::get('categories', [CategoryController::class, 'index']);

Route::prefix('product-sets')->group(function (): void {
    Route::get(null, [ProductSetController::class, 'index']);

    Route::middleware('auth:api')->group(function (): void {
        Route::get('id:{product_set:id}', [ProductSetController::class, 'show']);
        Route::post(null, [ProductSetController::class, 'store']);
        Route::patch('id:{product_set:id}', [ProductSetController::class, 'update']);
        Route::delete('id:{product_set:id}', [ProductSetController::class, 'destroy']);
        Route::post('reorder', [ProductSetController::class, 'reorder']);
        Route::post('reorder/id:{product_set:id}', [ProductSetController::class, 'reorder']);
    });

    Route::get('{product_set:slug}', [ProductSetController::class, 'show']);
});

Route::prefix('shipping-methods')->group(function (): void {
    Route::get(null, 'ShippingMethodController@index');
    Route::post('filter', [ShippingMethodController::class, 'index']);
    Route::post(null, 'ShippingMethodController@store')->middleware('auth:api');
    Route::post('order', 'ShippingMethodController@reorder')->middleware('auth:api');
    Route::patch('id:{shipping_method:id}', 'ShippingMethodController@update')
        ->middleware('auth:api');
    Route::delete('id:{shipping_method:id}', 'ShippingMethodController@destroy')
        ->middleware('auth:api');
});

Route::prefix('payment-methods')->group(function (): void {
    Route::get(null, 'PaymentMethodController@index');
    Route::post(null, 'PaymentMethodController@store')->middleware('auth:api');
    Route::patch('id:{payment_method:id}', 'PaymentMethodController@update')
        ->middleware('auth:api');
    Route::delete('id:{payment_method:id}', 'PaymentMethodController@destroy')
        ->middleware('auth:api');
});

Route::prefix('settings')->group(function (): void {
    Route::get(null, 'SettingController@index');
    Route::get('{setting:name}', 'SettingController@show');
    Route::post(null, 'SettingController@store')->middleware('auth:api');
    Route::patch('{setting:name}', 'SettingController@update')->middleware('auth:api');
    Route::delete('{setting:name}', 'SettingController@destroy')->middleware('auth:api');
});

Route::prefix('package-templates')->middleware('auth:api')->group(function (): void {
    Route::get(null, 'PackageTemplateController@index');
    Route::post(null, 'PackageTemplateController@store');
    Route::patch('id:{package:id}', 'PackageTemplateController@update');
    Route::delete('id:{package:id}', 'PackageTemplateController@destroy');
});

Route::get('countries', [CountriesController::class, 'index']);

Route::prefix('discounts')->group(function (): void {
    Route::get(null, [DiscountController::class, 'index'])->middleware('auth:api');
    Route::get('{discount:code}', [DiscountController::class, 'show']);
    Route::post(null, [DiscountController::class, 'store'])->middleware('auth:api');
    Route::patch('id:{discount:id}', [DiscountController::class, 'update'])
        ->middleware('auth:api');
});

Route::middleware('auth:api')->group(function (): void {
    Route::prefix('items')->group(function (): void {
        Route::get(null, 'ItemController@index');
        Route::post(null, 'ItemController@store');
        Route::get('id:{item:id}', 'ItemController@show');
        Route::patch('id:{item:id}', 'ItemController@update');
        Route::delete('id:{item:id}', 'ItemController@destroy');

        Route::get('id:{item:id}/deposits', 'DepositController@show');
        Route::post('id:{item:id}/deposits', 'DepositController@store');
    });

    Route::prefix('statuses')->group(function (): void {
        Route::get(null, 'StatusController@index');
        Route::post(null, 'StatusController@store');
        Route::post('order', 'StatusController@order');
        Route::patch('id:{status:id}', 'StatusController@update');
        Route::delete('id:{status:id}', 'StatusController@destroy');
    });

    Route::get('deposits', 'DepositController@index');

    Route::prefix('media')->group(function (): void {
        Route::post(null, 'MediaController@store');
        Route::delete('id:{media:id}', 'MediaController@destroy');
    });

    Route::prefix('schemas')->group(function (): void {
        Route::get(null, 'SchemaController@index');
        Route::post(null, 'SchemaController@store');
        Route::get('id:{schema:id}', 'SchemaController@show');
        Route::patch('id:{schema:id}', 'SchemaController@update');
        Route::delete('id:{schema:id}', 'SchemaController@destroy');
        Route::post('id:{schema:id}/attach/id:{product:id}', 'SchemaController@attach');
        Route::post('id:{schema:id}/detach/id:{product:id}', 'SchemaController@detach');
    });

    Route::prefix('options')->group(function (): void {
        Route::post(null, 'OptionController@store');
        Route::get('id:{option:id}', 'OptionController@show');
        Route::patch('id:{option:id}', 'OptionController@update');
        Route::delete('id:{option:id}', 'OptionController@destroy');
    });

    Route::prefix('auth')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('login-history', [AuthController::class, 'loginHistory']);
    });

    Route::prefix('apps')->group(function (): void {
        Route::get(null, [AppController::class, 'index']);
        Route::post(null, [AppController::class, 'store']);
    });

    Route::prefix('analytics')->group(function (): void {
        Route::get('payments', 'AnalyticsController@payments');
    });

    Route::prefix('tags')->group(function (): void {
        Route::get(null, [TagController::class, 'index']);
        Route::post(null, [TagController::class, 'store']);
        Route::patch('id:{tag:id}', [TagController::class, 'update']);
        Route::delete('id:{tag:id}', [TagController::class, 'destroy']);
    });
});

// External
Route::prefix('furgonetka')->group(function (): void {
    Route::post('webhook', 'External\FurgonetkaController@webhook');
    Route::post('create-package', 'External\FurgonetkaController@createPackage')
        ->middleware('auth:api');
});
