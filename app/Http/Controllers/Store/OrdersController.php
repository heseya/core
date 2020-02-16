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

    public function pay(Order $order, $method): JsonResponse
    {
        if (
            $order->payment_status !== 0 ||
            $order->shop_status === 3
        ) {
            return response()->json([
                'message' => 'Order not payable.',
            ], 406);
        }

        if (!array_key_exists($method, config('payable.aliases'))) {
            return response()->json([
                'message' => 'Unkown payment method.',
            ], 400);
        }

        $method_class = config('payable.aliases')[$method];

        $payment = $order->payments()
            ->where('method', $method)
            ->where('status', 'NEW')
            ->first();

        if (empty($payment)) {
            $payment = $order->payments()->create([
                'method' => $method,
                'amount' => $order->summary(),
                'currency' => 'PLN',
            ]);

            $payment->update(
                $method_class::generateUrl($payment)
            );
        }

        return response()->json([
            'url' => $payment->url,
        ]);
    }
}
