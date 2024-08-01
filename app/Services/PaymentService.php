<?php

namespace App\Services;

use App\Dtos\PaymentDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\PaymentStatus;
use App\Exceptions\ClientException;
use App\Exceptions\ServerException;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\PayPal;
use App\Payments\PayU;
use App\Payments\Przelewy24;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;
use Domain\Currency\Currency;
use Domain\PaymentMethods\Models\PaymentMethod;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Throwable;

final class PaymentService
{
    /**
     * @throws ClientException
     * @throws MathException
     * @throws MoneyMismatchException
     * @throws ServerException
     */
    public function getPayment(Order $order, PaymentMethod $paymentMethod, string $continueUrl): Payment
    {
        if ($order->paid) {
            throw new ClientException(Exceptions::CLIENT_ORDER_PAID);
        }

        if (!PaymentMethod::searchByCriteria([
            'id' => $paymentMethod->getKey(),
            'public' => true,
            'order_code' => $order->code,
        ])->exists()) {
            throw new ClientException(Exceptions::PAYMENT_METHOD_NOT_AVAILABLE_FOR_SHIPPING);
        }

        return $paymentMethod->alias === null
            ? $this->requestPaymentFromMicroservice($paymentMethod, $order, $continueUrl)
            : $this->createPaymentLegacy($paymentMethod, $order, $continueUrl);
    }

    /**
     * @throws ServerException
     */
    private function requestPaymentFromMicroservice(PaymentMethod $method, Order $order, string $continueUrl): Payment
    {
        $response = Http::post($method->url, [
            'continue_url' => $continueUrl,
            'order_id' => $order->getKey(),
            'api_url' => Config::get('app.url'),
        ]);

        $this->validatePaymentServiceResponse($response);

        return Payment::create(
            [
                'order_id' => $order->getKey(),
                'method_id' => $method->getKey(),
                'status' => $response->json('status'),
                'amount' => $response->json('amount'),
                'currency' => $response->json('currency'),
                'redirect_url' => $response->json('redirect_url'),
                'continue_url' => $response->json('continue_url'),
            ],
        );
    }

    /**
     * @throws ServerException
     */
    private function validatePaymentServiceResponse(Response $response): void
    {
        if (!$response->ok()) {
            throw new ServerException(Exceptions::SERVER_PAYMENT_MICROSERVICE_ERROR);
        }

        $validator = Validator::make($response->json(), [
            'status' => ['required', new Enum(PaymentStatus::class)],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', new Enum(Currency::class)],
            'redirect_url' => ['nullable', 'string', 'max:1000'],
            'continue_url' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            throw new ServerException(Exceptions::SERVER_PAYMENT_MICROSERVICE_ERROR, new ValidationException($validator));
        }
    }

    /**
     * @throws ClientException
     * @throws MathException
     * @throws MoneyMismatchException
     *
     * @deprecated
     */
    private function createPaymentLegacy(PaymentMethod $method, Order $order, string $continueUrl): Payment
    {
        if ($order->paid) {
            throw new ClientException(Exceptions::CLIENT_ORDER_PAID);
        }

        if (!array_key_exists($method->alias ?? '', Config::get('payable.aliases'))) {
            throw new ClientException(Exceptions::CLIENT_UNKNOWN_PAYMENT_METHOD);
        }

        /**
         * @var PayU|PayPal|Przelewy24 $methodClass
         */
        $methodClass = Config::get('payable.aliases')[$method->alias];

        /**
         * @var Payment $payment
         */
        $payment = $order->payments()->create([
            'method' => $method->alias,
            'method_id' => $method->getKey(),
            'amount' => $order->summary->minus($order->paid_amount),
            'currency' => $order->currency,
            'status' => PaymentStatus::PENDING,
            'continue_url' => $continueUrl,
        ]);

        try {
            $payment->update($methodClass::generateUrl($payment));
        } catch (Throwable) {
            $payment->update(['status' => PaymentStatus::FAILED]);
            throw new ClientException(Exceptions::CLIENT_GENERATE_PAYMENT_URL);
        }

        return $payment;
    }

    public function create(PaymentDto $dto): Payment
    {
        /** @var Order $order */
        $order = Order::query()->findOrFail($dto->getOrderId());

        return Payment::create([
            'currency' => $order->currency,
            ...$dto->toArray(),
        ]);
    }
}
