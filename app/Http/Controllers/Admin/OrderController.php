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
            ->paginate(20);

        return response()->view('admin/orders/index', [
            'orders' => $orders,
            'status' => new Status,
        ]);
    }

    public function view(Order $order)
    {
        return response()->view('admin/orders/view', [
            'order' => $order,
            'status' => new Status,
        ]);
    }

    public function createForm()
    {
        return response()->view('admin/orders/create');
    }
}
