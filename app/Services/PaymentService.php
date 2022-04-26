<?php

namespace App\Services;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\PaymentStatus;
use App\Exceptions\ClientException;
use App\Exceptions\ServerException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
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
        $paymentMethod = PaymentMethod::where('id', $method)->first();

        return $paymentMethod === null ?
            $this->createPayment($method, $order, $request)
            : $this->requestPaymentFromMicroservice($paymentMethod, $order, $request);
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

        return Payment::create(['order_id' => $order->getKey()] + $response->json());
    }

    private function createPayment(string $method, Order $order, Request $request): Payment
    {
        if (!array_key_exists($method, Config::get('payable.aliases'))) {
            throw new ClientException(Exceptions::CLIENT_UNKNOWN_PAYMENT_METHOD);
        }

        $method_class = Config::get('payable.aliases')[$method];

        /** @var Payment $payment */
        $payment = $order->payments()->create([
            'method' => $method,
            'amount' => $order->summary - $order->paid_amount,
            'status' => PaymentStatus::PENDING,
            'continue_url' => $request->input('continue_url'),
        ]);

        try {
            $payment->update($method_class::generateUrl($payment));
        } catch (Throwable $error) {
            throw new ClientException(Exceptions::CLIENT_GENERATE_PAYMENT_URL);
        }

        return $payment;
    }
}
