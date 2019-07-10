<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use anlutro\LaravelSettings\Facade as Setting;

use Auth;
use App\Product;
use App\Order;

class AdminController extends Controller
{
  public function orders(Request $request) {
    return response()->view('admin/orders', [
      'user' => Auth::user()
    ]);
  }

  public function order(Request $request, Order $order) {

    $order->address();

    $order['user'] = Auth::user();

    return response()->view('admin/order', $order);
  }

  public function products(Request $request) {
    return response()->view('admin/products', [
      'user' => Auth::user()
    ]);
  }

  public function productsSingle(Request $request) {
    return response()->view('admin/products-single', [
      'user' => Auth::user()
    ]);
  }

  public function chat(Request $request) {
    return response()->view('admin/chat', [
      'user' => Auth::user()
    ]);
  }

  public function chatSingle(Request $request) {
    return response()->view('admin/chat', [
      'user' => Auth::user()
    ]);
  }

  public function login(Request $request) {
    return response()->view('admin/login', [
      'user' => Auth::user()
    ]);
  }

  // USTAWIENIA
  public function settings(Request $request) {
    return response()->view('admin/settings', [
      'user' => Auth::user()
    ]);
  }

  public function info(Request $request) {
    return response()->view('admin/settings/info', [
      'version' => '0.1',
      'user' => Auth::user()
    ]);
  }

  public function email(Request $request) {

    $email = Setting::get('email.from.user', 'shop@kupdepth.pl');
    $gravatar = md5(strtolower(trim($email)));

    return response()->view('admin/settings/email', [
      'email' => $email,
      'gravatar' => $gravatar,
      'user' => Auth::user()
    ]);
  }

  public function emailConfig(Request $request) {

    return response()->view('admin/settings/email-config', [
      'old' => Setting::get('email'),
      'user' => Auth::user()
    ]);
  }

  public function notifications(Request $request) {
    return response()->view('admin/settings/notifications', [
      'user' => Auth::user()
    ]);
  }
}
