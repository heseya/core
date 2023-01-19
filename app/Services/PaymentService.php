<?php

namespace App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\PaymentStatus;
use App\Exceptions\ClientException;
use App\Exceptions\ServerException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Payments\PayPal;
use App\Payments\PayU;
use App\Payments\Przelewy24;
use App\Services\Contracts\PaymentServiceContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Throwable;

class PaymentService implements PaymentServiceContract
{
    /**
     * @throws ServerException|ClientException
     */
    public function getPayment(Order $order, string $method, Request $request): Payment
    {
        if ($order->paid) {
            throw new ClientException(Exceptions::CLIENT_ORDER_PAID);
        }

        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = PaymentMethod::query()
            ->where('id', $method)
            ->firstOr(fn () => throw new ClientException(Exceptions::CLIENT_UNKNOWN_PAYMENT_METHOD));

        return $paymentMethod->alias === null
            ? $this->requestPaymentFromMicroservice($paymentMethod, $order, $request)
            : $this->createPaymentLegacy($paymentMethod->alias, $order, $request);
    }

    private function requestPaymentFromMicroservice(PaymentMethod $method, Order $order, Request $request): Payment
    {
        $response = Http::post($method->url, [
            'continue_url' => $request->input('continue_url'),
            'order_id' => $order->getKey(),
            'api_url' => Config::get('app.url'),
        ]);

        if (!$response->ok()) {
            throw new ServerException(Exceptions::SERVER_PAYMENT_MICROSERVICE_ERROR);
        }

        return Payment::create(
            [
                'order_id' => $order->getKey(),
                'method_id' => $method->getKey(),
                'status' => $response->json('status'),
                'amount' => $response->json('amount'),
                'redirect_url' => $response->json('redirect_url'),
                'continue_url' => $response->json('continue_url'),
            ]
        );
    }

    private function createPaymentLegacy(string $method, Order $order, Request $request): Payment
    {
        if ($order->paid) {
            throw new ClientException(Exceptions::CLIENT_ORDER_PAID);
        }

        if (!array_key_exists($method, Config::get('payable.aliases'))) {
            throw new ClientException(Exceptions::CLIENT_UNKNOWN_PAYMENT_METHOD);
        }

        /**
         * @var PayU|PayPal|Przelewy24 $methodClass
         */
        $methodClass = Config::get('payable.aliases')[$method];

        $paymentMethod = PaymentMethod::searchByCriteria([
            'order_code' => $order->code,
            'alias' => $method,
        ])->first();

        if (!$paymentMethod) {
            throw new ClientException(Exceptions::PAYMENT_METHOD_NOT_AVAILABLE_FOR_SHIPPING);
        }

        /**
         * @var Payment $payment
         */
        $payment = $order->payments()->create([
            'method' => $method,
            'amount' => $order->summary - $order->paid_amount,
            'status' => PaymentStatus::PENDING,
            'continue_url' => $request->input('continue_url'),
        ]);

        try {
            $payment->update($methodClass::generateUrl($payment));
        } catch (Throwable) {
            throw new ClientException(Exceptions::CLIENT_GENERATE_PAYMENT_URL);
        }

        return $payment;
    }
}
