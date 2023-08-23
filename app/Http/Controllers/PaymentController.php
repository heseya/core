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
use App\Models\PaymentMethod;
use App\Payments\PayPal;
use App\Payments\PayU;
use App\Payments\Przelewy24;
use App\Services\PaymentService;
use Brick\Math\Exception\MathException;
use Brick\Money\Exception\MoneyMismatchException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    public function pay(Order $order, PaymentMethod $paymentMethod, PayRequest $request): JsonResource
    {
        return PaymentResource::make(
            $this->paymentService->getPayment(
                $order,
                $paymentMethod,
                $request->input('continue_url'),
            ),
        );
    }

    public function updatePayment(PaymentUpdateRequest $request, Payment $payment): JsonResource
    {
        /** @var PaymentDto $dto */
        $dto = PaymentDto::instantiateFromRequest($request);
        $payment->update($dto->toArray());

        return PaymentResource::make($payment);
    }

    /**
     * @throws MathException
     * @throws MoneyMismatchException
     */
    public function offlinePayment(Order $order): JsonResource
    {
        $payment = $order->payments()->create([
            'method' => 'offline',
            'amount' => $order->summary->minus($order->paid_amount),
            'status' => PaymentStatus::SUCCESSFUL,
            'currency' => $order->currency,
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

        return PaymentResource::make(
            $this->paymentService->create($dto),
        );
    }

    /**
     * Old method for translate communication with payments providers.
     *
     * @deprecated
     *
     * @throws ClientException
     */
    public function updatePaymentLegacy(string $method, Request $request): mixed
    {
        if (!array_key_exists($method, Config::get('payable.aliases'))) {
            throw new ClientException(Exceptions::CLIENT_UNKNOWN_PAYMENT_METHOD);
        }

        /**
         * @var PayU|PayPal|Przelewy24 $methodClass
         */
        $methodClass = Config::get('payable.aliases')[$method];

        return $methodClass::translateNotification($request);
    }
}
