<?php

namespace App\Http\Controllers;

use App\Order;
use App\Status;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FurgonetkaController extends Controller
{
    /**
     * Odbieranie statusów przesyłek z Furgonetka.pl
     */
    public function webhook(Request $request)
    {
        $request->validate([
            'package_id' => 'required',
            'package_no' => 'required',
            'partner_order_id' => 'required',

            'tracking.state' => 'required',
            'tracking.description' => 'required',
            'tracking.datetime' => 'required',
            'tracking.branch' => 'required',

            'control' => 'required',
        ]);

        $control = md5(
            $request->package_id .
            $request->package_no .
            $request->partner_order_id .
            $request->tracking['state'] .
            $request->tracking['description'] .
            $request->tracking['datetime'] .
            $request->tracking['branch'] .
            config('furgonetka.webhook_salt')
        );

        if ($control !== $request->control) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'control value not match',
            ], 400);
        }

        $order = Order::find($request->partner_order_id);

        if (empty($order)) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'order not found',
            ], 404);
        }

        $status = new Status;
        $order->update([
            'delivery_status' => $status->furgonetka_status[$request->tracking['state']],
        ]);

        return response()->json([
            'status' => 'OK',
        ], 200);
    }
}
