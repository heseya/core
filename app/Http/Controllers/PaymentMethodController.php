<?php

namespace App\Http\Controllers;

use App\Dtos\PaymentMethodDto;
use App\Http\Requests\PaymentMethodIndexRequest;
use App\Http\Requests\PaymentMethodStoreRequest;
use App\Http\Requests\PaymentMethodUpdateRequest;
use App\Http\Resources\PaymentMethodDetailsResource;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class PaymentMethodController extends Controller
{
    public function index(PaymentMethodIndexRequest $request): JsonResource
    {
        if ($request->has('shipping_method_id')) {
            $shipping_method = ShippingMethod::find($request->input('shipping_method_id'));
            $query = $shipping_method->paymentMethods();
        } else {
            $query = PaymentMethod::query();
        }

        if (!Auth::user()->can('payment_methods.show_hidden')) {
            $query->where('public', true);
        }

        return PaymentMethodResource::collection($query->get());
    }

    public function store(PaymentMethodStoreRequest $request): JsonResource
    {
        $dto = PaymentMethodDto::instantiateFromRequest($request);

        $payment_method = PaymentMethod::create($dto->toArray());

        return PaymentMethodDetailsResource::make($payment_method);
    }

    public function update(PaymentMethod $payment_method, PaymentMethodUpdateRequest $request): JsonResource
    {
        $dto = PaymentMethodDto::instantiateFromRequest($request);

        $payment_method->update($dto->toArray());

        return PaymentMethodResource::make($payment_method);
    }

    public function destroy(PaymentMethod $payment_method): JsonResponse
    {
        $payment_method->delete();

        return Response::json(null, 204);
    }
}
