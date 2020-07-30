<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Controllers\Swagger\ShippingMethodControllerSwagger;
use App\Http\Resources\ShippingMethodResource;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ShippingMethodController extends Controller implements ShippingMethodControllerSwagger
{
    public function index(): JsonResource
    {
        $query = ShippingMethod::select();

        if (Auth::check()) {
            $query->with('paymentMethods');
        } else {
            $query
                ->with(['paymentMethods' => fn ($q) => $q->where('public', true)])
                ->where('public', true);
        }

        return ShippingMethodResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'public' => 'boolean',
            'payment_methods' => 'array',
            'payment_methods.*' => 'uuid|exists:payment_methods,id',
        ]);

        $shipping_method = ShippingMethod::create($validated);
        $shipping_method->paymentMethods()->sync($request->input('payment_methods', []));

        return ShippingMethodResource::make($shipping_method)
            ->response()
            ->setStatusCode(201);
    }

    public function update(ShippingMethod $shipping_method, Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'price' => 'numeric',
            'public' => 'boolean',
            'payment_methods' => 'array',
            'payment_methods.*' => 'uuid|exists:payment_methods,id',
        ]);

        $shipping_method->update($validated);
        $shipping_method->paymentMethods()->sync($request->input('payment_methods', []));

        return ShippingMethodResource::make($shipping_method);
    }

    public function destroy(ShippingMethod $shipping_method)
    {
        if ($shipping_method->orders()->count() > 0) {
            return Error::abort(
                "Shipping method can't be deleted, because has relations.",
                409,
            );
        }

        $shipping_method->delete();

        return response()->json(null, 204);
    }
}
