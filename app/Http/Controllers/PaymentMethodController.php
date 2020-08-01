<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\PaymentMethodControllerSwagger;
use App\Http\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller implements PaymentMethodControllerSwagger
{
    public function index(Request $request): JsonResource
    {
         $request->validate([
            'shipping_method_id' => 'integer|exists:shipping_methods,id',
        ]);

        if ($request->input('shipping_method_id')) {
            $shipping_method = ShippingMethod::find($request->input('shipping_method_id'));
            $query = $shipping_method->paymentMethods();
        } else {
            $query = PaymentMethod::select();
        }

        if (!Auth::check()) {
            $query->where('public', true);
        }

        return PaymentMethodResource::collection($query->get());
    }

    public function store(Request $request)
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

    public function destroy(PaymentMethod $payment_method)
    {
        $payment_method->delete();

        return response()->json(null, 204);
    }
}
