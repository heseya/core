<?php

namespace App\Http\Controllers\Admin;

use Unirest;
use App\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    public function changeStatus(Request $request)
    {
        $order = Order::find($request->order_id);

        if(empty($order)) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        $order->update([
            $request->type . '_status' => $request->status,
        ]);

        return response()->json(null, 204);
    }

    public function upload(Request $request)
    {
        $body = Unirest\Request\Body::multipart([], ['photo' => $request->photo]);
        $response = Unirest\Request::post(config('cdn.host'), config('cdn.headers'), $body);

        // return $response->raw_body;
        return (config('cdn.host') . '/' . $response->body[0]->id);
    }
}
