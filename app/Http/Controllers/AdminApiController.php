<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Order;
use App\Product;
use App\Chat;

class AdminApiController extends Controller
{
  public function orders ()
  {
    $docs = Order::select('id', 'code', 'email', 'payment_status', 'shop_status', 'delivery_status', 'created_at')
    ->orderBy('created_at', 'desc')
    ->get();

    foreach($docs as $doc)
      $result[] = $doc->view();

    return response()->json($result);
  }

  public function products(Request $request)
  {
    $products = Product::all();

    foreach($products as $product) {
      $product->img = 'https://source.unsplash.com/collection/1085173/250x250?' . $product->id;
      $product->price = 200;
    }

    return response()->json($products);
  }

  public function chats ()
  {
    $chats = Chat::all();

    foreach($chats as $chat) {
      $chat->client;
      $chat->avatar = 'https://source.unsplash.com/collection/2013520/50x50?' . $chat->id;
      $chat->snippet = $chat->snippet();
    }

    return response()->json($chats);
  }
}
