<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CountriesController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductSetController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ShippingMethodController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(function (): void {
    Route::get('/reset-password/{token?}/{email?}', 'AuthController@showResetPasswordForm')
        ->middleware('can:auth.password_reset');
    Route::post('/reset-password', 'AuthController@resetPassword')
        ->middleware('can:auth.password_reset');
    Route::patch('/save-reset-password', 'AuthController@saveResetPassword')
        ->middleware('can:auth.password_reset');

    Route::middleware('auth:api')->group(function (): void {
        Route::get(null, 'UserController@index');
        Route::get('id:{user:id}', 'UserController@show');
        Route::post(null, 'UserController@store');
        Route::patch('id:{user:id}', 'UserController@update');
        Route::delete('id:{user:id}', 'UserController@destroy');
    });
});

Route::post('login', 'AuthController@login')->middleware('can:auth.login');
Route::patch('user/password', 'AuthController@changePassword')
    ->middleware('can:auth.password_change');

Route::prefix('products')->group(function (): void {
    Route::get(null, 'ProductController@index')
        ->middleware('can:products.show');
    Route::post(null, 'ProductController@store')
        ->middleware('can:products.add');
    Route::get('id:{product:id}', 'ProductController@show')
        ->middleware('can:products.show_details');
    Route::get('{product:slug}', 'ProductController@show')
        ->middleware('can:products.show_details');
    Route::patch('id:{product:id}', 'ProductController@update')
        ->middleware('can:products.edit');
    Route::delete('id:{product:id}', 'ProductController@destroy')
        ->middleware('can:products.remove');
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
    Route::post('{order:code}/pay/{method}', 'PaymentController@store')
        ->middleware('can:payments.add');
});

Route::any('payments/{method}', 'PaymentController@update')
    ->middleware('can:payments.edit');

Route::prefix('pages')->group(function (): void {
    Route::get(null, 'PageController@index')
        ->middleware('can:pages.show');
    Route::post(null, 'PageController@store')
        ->middleware('can:pages.add');
    Route::get('id:{page:id}', 'PageController@show')
        ->middleware('can:pages.show_details');
    Route::get('{page:slug}', 'PageController@show')
        ->middleware('can:pages.show_details');
    Route::patch('id:{page:id}', 'PageController@update')
        ->middleware('can:pages.edit');
    Route::delete('id:{page:id}', 'PageController@destroy')
        ->middleware('can:pages.remove');
    Route::post('order', 'PageController@reorder')
        ->middleware('can:pages.edit');
});

Route::get('brands', [BrandController::class, 'index'])
    ->middleware('can:product_sets.show');
Route::get('categories', [CategoryController::class, 'index'])
    ->middleware('can:product_sets.show');

Route::prefix('product-sets')->group(function (): void {
    Route::get(null, [ProductSetController::class, 'index']);

    Route::middleware('auth:api')->group(function (): void {
        Route::get('id:{product_set:id}', [ProductSetController::class, 'show']);
        Route::post(null, [ProductSetController::class, 'store']);
        Route::patch('id:{product_set:id}', [ProductSetController::class, 'update']);
        Route::post('id:{product_set:id}/products', [ProductSetController::class, 'attach']);
        Route::delete('id:{product_set:id}', [ProductSetController::class, 'destroy']);
        Route::post('reorder', [ProductSetController::class, 'reorder']);
        Route::post('reorder/id:{product_set:id}', [ProductSetController::class, 'reorder']);
    });

    Route::get('id:{product_set:id}/products', [ProductSetController::class, 'products']);
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
    Route::get(null, 'PaymentMethodController@index')
        ->middleware('can:payment_methods.show');
    Route::post(null, 'PaymentMethodController@store')
        ->middleware('can:payment_methods.add');
    Route::patch('id:{payment_method:id}', 'PaymentMethodController@update')
        ->middleware('can:payment_methods.edit');
    Route::delete('id:{payment_method:id}', 'PaymentMethodController@destroy')
        ->middleware('can:payment_methods.remove');
});

Route::prefix('settings')->group(function (): void {
    Route::get(null, 'SettingController@index');
    Route::get('{setting:name}', 'SettingController@show');
    Route::post(null, 'SettingController@store')->middleware('auth:api');
    Route::patch('{setting:name}', 'SettingController@update')->middleware('auth:api');
    Route::delete('{setting:name}', 'SettingController@destroy')->middleware('auth:api');
});

Route::prefix('package-templates')->group(function (): void {
    Route::get(null, 'PackageTemplateController@index')
        ->middleware('can:packages.show');
    Route::post(null, 'PackageTemplateController@store')
        ->middleware('can:packages.add');
    Route::patch('id:{package:id}', 'PackageTemplateController@update')
        ->middleware('can:packages.edit');
    Route::delete('id:{package:id}', 'PackageTemplateController@destroy')
        ->middleware('can:packages.remove');
});

Route::get('countries', [CountriesController::class, 'index'])
    ->middleware('can:countries.show');

Route::prefix('discounts')->group(function (): void {
    Route::get(null, [DiscountController::class, 'index'])
        ->middleware('can:discounts.show');
    Route::get('{discount:code}', [DiscountController::class, 'show'])
        ->middleware('can:discounts.show_details');
    Route::post(null, [DiscountController::class, 'store'])
        ->middleware('can:discounts.add');
    Route::patch('id:{discount:id}', [DiscountController::class, 'update'])
        ->middleware('can:discounts.edit');
});

Route::prefix('roles')->middleware('auth:api')->group(function (): void {
    Route::get(null, [RoleController::class, 'index']);
    Route::post(null, [RoleController::class, 'store']);
    Route::get('id:{role:id}', [RoleController::class, 'show']);
    Route::patch('id:{role:id}', [RoleController::class, 'update']);
    Route::delete('id:{role:id}', [RoleController::class, 'destroy']);
});

Route::get('permissions', [PermissionController::class, 'index'])->middleware('auth:api');

Route::prefix('analytics')->group(function (): void {
    Route::get('payments', 'AnalyticsController@payments')
        ->middleware('can:analytics.payments');
});

Route::prefix('auth')->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth:api');
    Route::get('login-history', [AuthController::class, 'loginHistory'])
        ->middleware('can:auth.sessions.show');
    Route::get('kill-session/id:{id}', [AuthController::class, 'killActiveSession'])
        ->middleware('can:auth.sessions.revoke');
    Route::get('kill-all-sessions', [AuthController::class, 'killAllSessions'])
        ->middleware('can:auth.sessions.revoke');
    Route::get('profile', [AuthController::class, 'profile']);
});

Route::get('deposits', 'DepositController@index')
    ->middleware('can:deposits.show');

Route::prefix('items')->group(function (): void {
    Route::get(null, 'ItemController@index')
        ->middleware('can:items.show');
    Route::post(null, 'ItemController@store')
        ->middleware('can:items.add');
    Route::get('id:{item:id}', 'ItemController@show')
        ->middleware('can:items.show_details');
    Route::patch('id:{item:id}', 'ItemController@update')
        ->middleware('can:items.edit');
    Route::delete('id:{item:id}', 'ItemController@destroy')
        ->middleware('can:items.remove');

    Route::get('id:{item:id}/deposits', 'DepositController@show')
        ->middleware('can:deposits.show');
    Route::post('id:{item:id}/deposits', 'DepositController@store')
        ->middleware('can:deposits.add');
});

Route::middleware('auth:api')->group(function (): void {
    Route::prefix('statuses')->group(function (): void {
        Route::get(null, 'StatusController@index');
        Route::post(null, 'StatusController@store');
        Route::post('order', 'StatusController@order');
        Route::patch('id:{status:id}', 'StatusController@update');
        Route::delete('id:{status:id}', 'StatusController@destroy');
    });

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

    Route::prefix('apps')->group(function (): void {
        Route::get(null, [AppController::class, 'index']);
        Route::post(null, [AppController::class, 'store']);
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
