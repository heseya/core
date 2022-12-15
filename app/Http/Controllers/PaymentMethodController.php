<?php

namespace App\Http\Controllers;

use App\Dtos\PaymentMethodIndexDto;
use App\Http\Requests\PaymentMethodIndexRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Contracts\PaymentMethodServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'alias' => 'required|string|max:255',
            'public' => 'boolean',
        ]);

        $payment_method = PaymentMethod::create($validated);

        return PaymentMethodResource::make($payment_method);
    }

    public function update(PaymentMethod $payment_method, Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'alias' => 'string|max:255',
            'public' => 'boolean',
        ]);

        $payment_method->update($validated);

        return PaymentMethodResource::make($payment_method);
    }

    public function destroy(PaymentMethod $payment_method): JsonResponse
    {
        $payment_method->delete();

        return Response::json(null, 204);
    }
}
