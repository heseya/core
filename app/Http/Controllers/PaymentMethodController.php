<?php

namespace App\Http\Controllers;

use App\Dtos\PaymentMethodDto;
use App\Dtos\PaymentMethodIndexDto;
use App\Http\Requests\PaymentMethodIndexRequest;
use App\Http\Requests\PaymentMethodStoreRequest;
use App\Http\Requests\PaymentMethodUpdateRequest;
use App\Http\Resources\PaymentMethodDetailsResource;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Services\Contracts\PaymentMethodServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class PaymentMethodController extends Controller
{
    public function __construct(
        private PaymentMethodServiceContract $paymentMethodService,
    ) {
    }

    public function index(PaymentMethodIndexRequest $request): JsonResource
    {
        return PaymentMethodResource::collection(
            $this->paymentMethodService->index(PaymentMethodIndexDto::instantiateFromRequest($request))
        );
    }

    public function show(PaymentMethod $paymentMethod): JsonResource
    {
        return PaymentMethodDetailsResource::make($paymentMethod);
    }

    public function store(PaymentMethodStoreRequest $request): JsonResource
    {
        $dto = PaymentMethodDto::instantiateFromRequest($request);

        $payment_method = $this->paymentMethodService->store($dto);

        return PaymentMethodDetailsResource::make($payment_method);
    }

    public function update(PaymentMethod $payment_method, PaymentMethodUpdateRequest $request): JsonResource
    {
        $dto = PaymentMethodDto::instantiateFromRequest($request);

        $this->paymentMethodService->update($payment_method, $dto);

        return PaymentMethodDetailsResource::make($payment_method);
    }

    public function destroy(PaymentMethod $payment_method): JsonResponse
    {
        $payment_method->delete();

        return Response::json(null, 204);
    }
}
