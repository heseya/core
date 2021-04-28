<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Controllers\Swagger\PaymentControllerSwagger;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Throwable;

class PaymentController extends Controller implements PaymentControllerSwagger
{
    public function store(Order $order, string $method, Request $request)
    {
        $request->validate([
            'continue_url' => 'required|string',
        ]);

        if ($order->isPayed()) {
            return Error::abort('Order is already paid.', 409);
        }

        if (!array_key_exists($method, config('payable.aliases'))) {
            return Error::abort('Unknown payment method.', 404);
        }

        $method_class = config('payable.aliases')[$method];

        $payment = $order->payments()->create([
            'method' => $method,
            'amount' => $order->summary - $order->payed,
            'payed' => false,
            'continue_url' => $request->input('continue_url'),
        ]);

//        try {
            $payment->update($method_class::generateUrl($payment));
//        } catch (Throwable $e) {
//            return Error::abort('Cannot generate payment url.', 500);
//        }

        return PaymentResource::make($payment);
    }

    public function update(string $method, Request $request)
    {
        if (!array_key_exists($method, config('payable.aliases'))) {
            return Error::abort('Unknown payment method.', 404);
        }

        $method_class = config('payable.aliases')[$method];

        return $method_class::translateNotification($request);
    }
}
