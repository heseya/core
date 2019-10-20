<?php

namespace App\Http\Controllers;

use App\Chat;

use App\Order;
use App\Product;
use App\Status;use Illuminate\Http\Request;

class AdminApiController extends Controller
{
    public function orders()
    {
        $orders = Order::select('id', 'code', 'email', 'payment_status', 'shop_status', 'delivery_status', 'created_at', 'delivery_address')
            ->orderBy('created_at', 'desc')
            ->get();

        $status = new Status;

        foreach ($orders as $order) {
            $result[] = [
                'id' => $order->id,
                'title' => $order->deliveryAddress->name ?? $order->code,
                'email' => $order->email,
                'sum' => number_format(rand(5000, 20000) / 100, 2, ',', ' ') . ' zł',
                'created_at' => $order->created_at,
                'status' => [
                    $status->payment_status[$order->payment_status]['color'],
                    $status->shop_status[$order->shop_status]['color'],
                    $status->delivery_status[$order->delivery_status]['color'],
                ],
            ];
        }

        return response()->json($result);
    }

    public function products(Request $request)
    {
        $products = Product::all();

        foreach ($products as $product) {
            $product->img = 'https://source.unsplash.com/collection/1085173/250x250?' . $product->id;
            $product->price = 200;
        }

        return response()->json($products);
    }

    public function chats()
    {
        $chats = Chat::all();

        foreach ($chats as $chat) {
            $chat->client;
            $chat->avatar = 'https://source.unsplash.com/collection/2013520/50x50?' . $chat->id;
            $chat->snippet = $chat->snippet();
        }

        return response()->json($chats);
    }

    public function changeStatus(Request $request)
    {
        $order = Order::find($request->order_id);
        $order->update([
            $request->type . '_status' => $request->status,
        ]);

        return response()->json(null, 204);
    }
}
