<?php

namespace App\Http\Controllers\Admin;

use App\Order;
use App\Status;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::select('id', 'code', 'email', 'payment_status', 'shop_status', 'delivery_status', 'created_at', 'delivery_address')
            ->orderBy('created_at', 'desc')
            ->get();

        $status = new Status;

        foreach ($orders as $order) {
            $ordersFormat[] = [
                'id' => $order->id,
                'title' => $order->deliveryAddress->name ?? $order->code,
                'email' => $order->email,
                'sum' => number_format(rand(5000, 20000) / 100, 2, ',', ' ') . ' zł',
                'created_at' => $order->created_at,
                'status' => [
                    'payment' => $status->payment_status[$order->payment_status]['color'],
                    'shop' => $status->shop_status[$order->shop_status]['color'],
                    'delivery' => $status->delivery_status[$order->delivery_status]['color'],
                ],
            ];
        }

        return response()->view('admin/orders/index', [
            'user' => Auth::user(),
            'orders' => $ordersFormat ?? [],
        ]);
    }

    public function single(Order $order)
    {
        return response()->view('admin/orders/single', [
            'order' => $order,
            'status' => new Status,
            'user' => Auth::user(),
        ]);
    }

    public function addForm()
    {
        return response()->view('admin/orders/add', [
            'user' => Auth::user(),
        ]);
    }
}
