<?php

use App\Http\Controllers\MetadataController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\CanDownloadDocument;
use App\Models\Order;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('orders')->group(function (): void {
    Route::get('/', [OrderController::class, 'index'])
        ->middleware('can:orders.show');
    Route::post('/', [OrderController::class, 'store'])
        ->middleware('can:orders.add');

    /** @deprecated  */
    Route::get('my', function (): Application|RedirectResponse|\Illuminate\Foundation\Application {
        return redirect('my/orders');
    });
    /** @deprecated  */
    Route::get('my/{order:code}', function (Order $order): Application|RedirectResponse|\Illuminate\Foundation\Application {
        return redirect('my/orders/' . $order->code);
    });
    /** @deprecated  */
    Route::get('my-products', function (): Application|RedirectResponse|\Illuminate\Foundation\Application {
        return redirect('my/products');
    });

    Route::get('id:{order:id}', [OrderController::class, 'show'])
        ->middleware('can:orders.show_details');
    Route::patch('id:{order:id}/status', [OrderController::class, 'updateStatus'])
        ->middleware('can:orders.edit.status');
    Route::patch('id:{order:id}', [OrderController::class, 'update'])
        ->middleware('can:orders.edit');
    Route::patch('id:{order:id}/metadata', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:orders.edit');
    Route::patch('id:{order:id}/metadata-private', [MetadataController::class, 'updateOrCreate'])
        ->middleware('can:orders.edit');
    Route::patch('id:{order:id}/products/id:{product:id}', [OrderController::class, 'updateOrderProduct'])
        ->middleware('can:orders.edit');
    Route::post('id:{order:id}/send-urls', [OrderController::class, 'sendUrls'])
        ->middleware('can:orders.show_details');
    Route::get('{order:code}', [OrderController::class, 'showPublic'])
        ->middleware('can:orders.show_summary');

    Route::post('id:{order:id}/docs', [OrderController::class, 'storeDocument'])
        ->middleware('can:orders.edit');
    Route::delete('id:{order:id}/docs/id:{document}', [OrderController::class, 'deleteDocument'])
        ->middleware('can:orders.edit');
    Route::post('id:{order:id}/docs/send', [OrderController::class, 'sendDocuments']);
    Route::get('id:{order:id}/docs/id:{document}/download', [OrderController::class, 'downloadDocument'])
        ->middleware(CanDownloadDocument::class);

    Route::post('{order:code}/pay/offline', [PaymentController::class, 'offlinePayment'])
        ->middleware('can:payments.offline');
    Route::post('{order:code}/pay/id:{paymentMethod}', [PaymentController::class, 'pay'])
        ->middleware('can:payments.add');
});

Route::prefix('my')->group(function (): void {
    Route::get('orders', [OrderController::class, 'indexUserOrder'])
        ->middleware('can:orders.show_own');
    Route::get('orders/products', [OrderController::class, 'myOrderProducts'])
        ->middleware('can:authenticated');
    Route::get('orders/{order:code}', [OrderController::class, 'showUserOrder'])
        ->middleware('can:orders.show_own');
});
