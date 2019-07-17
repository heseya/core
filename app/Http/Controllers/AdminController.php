<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use anlutro\LaravelSettings\Facade as Setting;

use Auth;

use App\User;
use App\Product;
use App\Order;

use App\Mail\NewAdmin;
use App\Mail\Test;

class AdminController extends Controller
{
  public function orders(Request $request)
  {
    return response()->view('admin/orders', [
      'user' => Auth::user()
    ]);
  }

  public function order(Request $request, Order $order)
  {
    $order->address();

    $order['user'] = Auth::user();

    return response()->view('admin/order', $order);
  }

  public function ordersAdd(Request $request)
  {
    return response()->view('admin/orders-add', [
      'user' => Auth::user()
    ]);
  }

  public function products(Request $request)
  {
    return response()->view('admin/products', [
      'user' => Auth::user()
    ]);
  }

  public function productsSingle(Request $request)
  {
    return response()->view('admin/products-single', [
      'user' => Auth::user()
    ]);
  }

  public function chats(Request $request)
  {
    return response()->view('admin/chats', [
      'user' => Auth::user()
    ]);
  }

  public function chat(Request $request)
  {
    return response()->view('admin/chat', [
      'user' => Auth::user()
    ]);
  }

  public function login(Request $request)
  {
    return response()->view('admin/login', [
      'user' => Auth::user()
    ]);
  }

  // USTAWIENIA
  public function settings(Request $request)
  {
    return response()->view('admin/settings', [
      'user' => Auth::user()
    ]);
  }

  public function info(Request $request)
  {
    return response()->view('admin/settings/info', [
      'version' => '0.1',
      'user' => Auth::user()
    ]);
  }

  public function email(Request $request)
  {
    $email = Setting::get('email.from.user');
    $gravatar = md5(strtolower(trim($email)));

    return response()->view('admin/settings/email', [
      'name' => Setting::get('email.name'),
      'email' => $email,
      'gravatar' => $gravatar,
      'user' => Auth::user(),
      'imap' => extension_loaded('imap')
    ]);
  }

  public function emailConfig(Request $request)
  {
    $old = Setting::get('email', [
      'name' => '',
      'from' => [
        'host' => '',
        'port' => 587,
        'user' => '',
        'password' => ''
      ],
      'to' => [
        'host' => '',
        'port' => 993,
        'user' => '',
        'password' => ''
      ]
    ]);

    return response()->view('admin/settings/email-config', [
      'old' => $old,
      'user' => Auth::user()
    ]);
  }

  public function emailConfigStore(Request $request)
  {
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
    Artisan::call('config:cache');

    return redirect('admin/settings/email');
  }

  public function emailTest(Request $request)
  {
    Mail::to(Auth::user()->email)->send(new Test());

    return redirect('admin/settings/email');
  }

  public function accounts(Request $request)
  {
    $accounts = User::all();

    return response()->view('admin/settings/accounts', [
      'accounts' => $accounts,
      'user' => Auth::user()
    ]);
  }

  public function accountsAdd(Request $request)
  {
    return response()->view('admin/settings/accounts-add', [
      'user' => Auth::user()
    ]);
  }

  public function accountsStore(Request $request)
  {
    $password = str_random(8);

    Mail::to($_POST['email'])->send(new NewAdmin($_POST['email'], $password));

    User::create([
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'password' => Hash::make($password)
    ]);

    return redirect('/admin/settings/accounts');
  }

  public function notifications(Request $request)
  {
    return response()->view('admin/settings/notifications', [
      'user' => Auth::user()
    ]);
  }
}
