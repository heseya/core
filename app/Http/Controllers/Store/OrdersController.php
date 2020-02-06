<?php

namespace App\Http\Controllers\Store;

use App\Order;
use App\Status;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class OrdersController extends Controller
{
    public function view(Order $order): JsonResponse
    {
        return response()->json([
            'code' => $order->code,
            'statuses' => [
                'payment' => Status::payment($order->payment_status),
                'shop' => Status::shop($order->shop_status),
                'delivery' => Status::delivery($order->delivery_status),
            ],
        ]);
    }
}
