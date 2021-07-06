<?php

namespace App\Http\Controllers;

use App\Exceptions\StoreException;
use App\Http\Controllers\Swagger\PaymentControllerSwagger;
use App\Http\Requests\Payments\PaymentStoreRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Throwable;

class PaymentController extends Controller implements PaymentControllerSwagger
{
    public function store(Order $order, string $method, PaymentStoreRequest $request): JsonResource
    {
        if ($order->isPayed()) {
            throw new StoreException('Order is already paid.');
        }

        if (!array_key_exists($method, config('payable.aliases'))) {
            throw new StoreException('Unknown payment method.');
        }

        $method_class = config('payable.aliases')[$method];

        $payment = $order->payments()->create([
            'method' => $method,
            'amount' => $order->summary - $order->payed,
            'payed' => false,
            'continue_url' => $request->input('continue_url'),
        ]);

        try {
            $payment->update($method_class::generateUrl($payment));
        } catch (Throwable $error) {
            throw new StoreException('Cannot generate payment url.');
        }

        return PaymentResource::make($payment);
    }

    public function update(string $method, Request $request)
    {
        if (!array_key_exists($method, config('payable.aliases'))) {
            throw new StoreException('Unknown payment method.');
        }

        $method_class = config('payable.aliases')[$method];

        return $method_class::translateNotification($request);
    }
}
