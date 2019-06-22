<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;

class AdminApiController extends Controller
{
  public function orders () {
    $docs = Order::select('id', 'code', 'payment_status', 'shop_status', 'delivery_status', 'created_at')
    ->orderBy('created_at', 'desc')
    ->limit(15)
    ->get();

    foreach($docs as $doc)
      $result[] = $doc->view();

    return $result;
  }
}
