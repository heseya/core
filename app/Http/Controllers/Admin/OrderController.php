<?php

namespace App\Http\Controllers\Admin;

use App\Order;
use App\Status;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::orderBy('created_at', 'desc')->paginate(20);

        return view('admin.orders.index', [
            'orders' => $orders,
            'status' => new Status,
        ]);
    }

    public function view(Order $order)
    {
        return view('admin.orders.view', [
            'order' => $order,
            'status' => new Status,
        ]);
    }

    public function createForm()
    {
        return view('admin.orders.form');
    }

    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|max:16',
            // 'client_id' => 'exists:clients,id',
            'deliveryAddress.country' => 'string|size:2',
        ]);

        $order = new Order($request->all());

        $deliveryAddress = $order->deliveryAddress()->firstOrCreate($request->deliveryAddress);
        $order->delivery_address = $deliveryAddress->id;
        $order->save();
        $order->saveItems($request->items);

        // logi
        $order->logs()->create([
            'content' => 'Utworzenie zamówienia.',
            'user' => auth()->user()->name,
        ]);

        return redirect()->route('orders.view', $order->code);
    }

    public function updateForm(Order $order)
    {
        return view('admin.orders.form', [
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
        $order->items()->delete();
        $order->saveItems($request->items);

        // logi
        $order->logs()->create([
            'content' => 'Edycja.',
            'user' => auth()->user()->name,
        ]);

        return redirect()->route('orders', $order->code);
    }

    public function updateStatus(Order $order, Request $request)
    {
        $order->update([
            $request->type . '_status' => $request->status,
        ]);

        // logi
        $order->logs()->create([
            'content' => 'Zmiana statusu.',
            'user' => auth()->user()->name,
        ]);

        return response()->json(null, 204);
    }
}
