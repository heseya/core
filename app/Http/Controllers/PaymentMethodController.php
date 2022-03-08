<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentMethodIndexRequest;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
