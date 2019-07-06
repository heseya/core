<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use App\Product;
use App\Order;

class AdminController extends Controller
{
  public function orders(Request $request) {
    return response()->view('admin/orders');
  }

  public function order(Request $request, Order $order) {

    $order->address();

    return response()->view('admin/order', $order);
  }

  public function products(Request $request) {
    return response()->view('admin/products');
  }

  public function productsSingle(Request $request) {
    return response()->view('admin/products-single');
  }

  public function chat(Request $request) {
    return response()->view('admin/chat');
  }

  public function chatSingle(Request $request) {
    return response()->view('admin/chat');
  }

  public function settings(Request $request) {
    return response()->view('admin/settings', [
      'user' => Auth::user()
    ]);
  }

  public function info(Request $request) {
    return response()->view('admin/info', [
      'version' => '0.1'
    ]);
  }

  public function login(Request $request) {
    return response()->view('admin/login');
  }
}
