<?php

namespace App\Http\Controllers\Admin;

use App\Order;
use App\Status;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::select('id', 'code', 'email', 'payment_status', 'shop_status', 'delivery_status', 'created_at', 'delivery_address')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin/orders/index', [
            'orders' => $orders,
            'status' => new Status,
        ]);
    }

    public function view(Order $order)
    {
        return view('admin/orders/view', [
            'order' => $order,
            'status' => new Status,
        ]);
    }

    public function createForm()
    {
        return view('admin/orders/form');
    }

    public function create(Request $request)
    {
        dd($request);

        $order = new Order($request->all());

        $deliveryAddress = $order->deliveryAddress()->firstOrCreate($request->deliveryAddress);
        $order->delivery_address = $deliveryAddress->id;
        $order->save();

        // logi
        $order->logs()->create([
            'content' => 'Utworzenie zamówienia.',
            'user' => Auth::user()->name,
        ]);

        return redirect('/admin/orders/' . $order->id);
    }

    public function updateForm(Order $order)
    {
        return view('admin/orders/form', [
            'order' => $order,
        ]);
    }

    public function update(Order $order, Request $request)
    {
        $order->fill($request->all());

        $order->delivery_address = null;
        $deliveryAddress = $order->deliveryAddress()->firstOrCreate($request->deliveryAddress);
        $order->delivery_address = $deliveryAddress->id;
        $order->save();

        // logi
        $order->logs()->create([
            'content' => 'Edycja zamówienia.',
            'user' => Auth::user()->name,
        ]);

        return redirect('/admin/orders/' . $order->id);
    }
}
