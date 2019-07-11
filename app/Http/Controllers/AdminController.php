<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use anlutro\LaravelSettings\Facade as Setting;

use Auth;

use App\User;
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

    $email = Setting::get('email.from.user');
    $gravatar = md5(strtolower(trim($email)));

    return response()->view('admin/settings/email', [
      'name' => Setting::get('email.name'),
      'email' => $email,
      'gravatar' => $gravatar,
      'user' => Auth::user()
    ]);
  }

  public function emailConfig(Request $request) {

    $old = Setting::get('email');

    $old['to']['port'] = empty($old['to']['port']) ? 993 : $old['to']['port'];
    $old['from']['port'] = empty($old['from']['port']) ? 587 : $old['from']['port'];

    return response()->view('admin/settings/email-config', [
      'old' => $old,
      'user' => Auth::user()
    ]);
  }

  public function emailConfigStore(Request $request) {

    Setting::set('email', [
      'to' => [
        'user' => $_POST['to-user'],
        'password' => $_POST['to-password'],
        'host' => $_POST['to-host'],
        'port' => $_POST['to-port'] ?? 993
      ],
      'from' => [
        'user' => $_POST['from-user'],
        'password' => $_POST['from-password'],
        'host' => $_POST['from-host'],
        'port' => $_POST['from-port'] ?? 587
      ]
    ]);
    Setting::save();

    return redirect('admin/settings/email');
  }

  public function accounts(Request $request) {

    $accounts = User::all();

    return response()->view('admin/settings/accounts', [
      'accounts' => $accounts,
      'user' => Auth::user()
    ]);
  }

  public function notifications(Request $request) {
    return response()->view('admin/settings/notifications', [
      'user' => Auth::user()
    ]);
  }
}
