<?php

namespace App\Http\Controllers;

use App\Dtos\PaymentDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Enums\PaymentStatus;
use App\Exceptions\ClientException;
use App\Http\Requests\Payments\PayRequest;
use App\Http\Requests\PaymentStoreRequest;
use App\Http\Requests\PaymentUpdateRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Contracts\PaymentServiceContract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class PaymentController extends Controller
{
    public function __construct(private PaymentServiceContract $paymentService)
    {
    }

    public function pay(Order $order, string $method, PayRequest $request): JsonResource
    {
        if ($order->paid) {
            throw new ClientException(Exceptions::CLIENT_ORDER_PAID);
        }

        $payment = $this->paymentService->getPayment($order, $method, $request);
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
            'status' => PaymentStatus::SUCCESSFUL,
        ]);

        return PaymentResource::make($payment);
    }

    public function index(): JsonResource
    {
        return PaymentResource::collection(Payment::all());
    }

    public function show(Payment $payment): JsonResource
    {
        return PaymentResource::make($payment);
    }

    public function store(PaymentStoreRequest $request): JsonResource
    {
        /** @var PaymentDto $dto */
        $dto = PaymentDto::instantiateFromRequest($request);

        return PaymentResource::make(Payment::create($dto->toArray()));
    }

    public function updatePayment(PaymentUpdateRequest $request, Payment $payment): JsonResource
    {
        /** @var PaymentDto $dto */
        $dto = PaymentDto::instantiateFromRequest($request);
        $payment->update($dto->toArray());

        return PaymentResource::make($payment);
    }
}
