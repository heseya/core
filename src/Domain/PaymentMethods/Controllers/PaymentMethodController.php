<?php

declare(strict_types=1);

namespace Domain\PaymentMethods\Controllers;

use App\Http\Controllers\Controller;
use Domain\PaymentMethods\Dtos\PaymentMethodCreateDto;
use Domain\PaymentMethods\Dtos\PaymentMethodIndexDto;
use Domain\PaymentMethods\Dtos\PaymentMethodUpdateDto;
use Domain\PaymentMethods\Models\PaymentMethod;
use Domain\PaymentMethods\Resources\PaymentMethodDetailsResource;
use Domain\PaymentMethods\Resources\PaymentMethodResource;
use Domain\PaymentMethods\Services\PaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

final class PaymentMethodController extends Controller
{
    public function __construct(
        private readonly PaymentMethodService $paymentMethodService,
    ) {}

    public function index(PaymentMethodIndexDto $dto): JsonResource
    {
        return PaymentMethodResource::collection(
            $this->paymentMethodService->index($dto),
        );
    }

    public function show(PaymentMethod $paymentMethod): JsonResource
    {
        return PaymentMethodDetailsResource::make($paymentMethod);
    }

    public function store(PaymentMethodCreateDto $dto): JsonResource
    {
        $payment_method = $this->paymentMethodService->store($dto);

        return PaymentMethodDetailsResource::make($payment_method);
    }

    public function update(PaymentMethod $payment_method, PaymentMethodUpdateDto $dto): JsonResource
    {
        $this->paymentMethodService->update($payment_method, $dto);

        return PaymentMethodDetailsResource::make($payment_method);
    }

    public function destroy(PaymentMethod $payment_method): JsonResponse
    {
        $payment_method->delete();

        return Response::json(null, 204);
    }
}
