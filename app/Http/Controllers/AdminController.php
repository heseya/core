<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use App\Products;

class AdminController extends Controller
{
  public function orders(Request $request) {
    return response()->view('admin/orders');
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
    return response()->view('admin/info');
  }

  public function login(Request $request) {
    return response()->view('admin/login');
  }
}
