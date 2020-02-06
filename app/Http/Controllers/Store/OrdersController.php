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
        $status = new Status;

        return response()->json([
            'code' => $order->code,
            'payment_status' => $status->payment_status[$order->payment_status]['name'],
            'shop_status' => $status->shop_status[$order->shop_status]['name'],
            'delivery_status' => $status->delivery_status[$order->delivery_status]['name'],
        ]);
    }
}
