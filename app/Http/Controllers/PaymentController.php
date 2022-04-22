<?php

namespace App\Http\Controllers;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Http\Requests\Payments\PaymentStoreRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Throwable;

class PaymentController extends Controller
{
    public function store(Order $order, string $method, PaymentStoreRequest $request): JsonResource
    {
        if ($order->paid) {
            throw new ClientException(Exceptions::CLIENT_ORDER_PAID);
        }

        if (!array_key_exists($method, Config::get('payable.aliases'))) {
            throw new ClientException(Exceptions::CLIENT_UNKNOWN_PAYMENT_METHOD);
        }

        $method_class = Config::get('payable.aliases')[$method];

        $payment = $order->payments()->create([
            'method' => $method,
            'amount' => $order->summary - $order->paid_amount,
            'paid' => false,
            'continue_url' => $request->input('continue_url'),
        ]);

        try {
            $payment->update($method_class::generateUrl($payment));
        } catch (Throwable $error) {
            throw new ClientException(Exceptions::CLIENT_GENERATE_PAYMENT_URL);
        }

        return PaymentResource::make($payment);
    }

    public function update(string $method, Request $request): mixed
    {
        if (!array_key_exists($method, Config::get('payable.aliases'))) {
            throw new ClientException(Exceptions::CLIENT_UNKNOWN_PAYMENT_METHOD);
        }

        $method_class = Config::get('payable.aliases')[$method];

        return $method_class::translateNotification($request);
    }

    public function offlinePayment(Order $order): JsonResource
    {
        $payment = $order->payments()->create([
            'method' => 'offline',
            'amount' => $order->summary - $order->paid_amount,
            'paid' => true,
        ]);

        return PaymentResource::make($payment);
    }
}
