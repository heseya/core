<?php

namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Http\Controllers\Swagger\ShippingMethodControllerSwagger;
use App\Http\Requests\ShippingMethodIndexRequest;
use App\Http\Requests\ShippingMethodOrderRequest;
use App\Http\Resources\ShippingMethodResource;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ShippingMethodController extends Controller implements ShippingMethodControllerSwagger
{
    public function index(ShippingMethodIndexRequest $request): JsonResource
    {
        $query = ShippingMethod::query()->orderBy('order');

        if (Auth::check()) {
            $query->with('paymentMethods');
        } else {
            $query
                ->with(['paymentMethods' => fn ($q) => $q->where('public', true)])
                ->where('public', true);
        }

        if ($request->has('country')) {
            $query->where(function (Builder $query) use ($request) {
                $query->where(function (Builder $query) use ($request) {
                    $query
                        ->where('black_list', false)
                        ->whereHas('countries', fn ($q) => $q->where('code', $request->input('country')));
                })
                    ->orWhere(function (Builder $query) use ($request) {
                        $query
                            ->where('black_list', true)
                            ->whereDoesntHave('countries', fn ($q) => $q->where('code', $request->input('country')));
                    });
            });
        }

        return ShippingMethodResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric'],
            'public' => 'boolean',
            'black_list' => 'boolean',
            'payment_methods' => 'array',
            'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
            'countries' => 'array',
            'countries.*' => ['string', 'size:2', 'exists:countries,code'],
        ]);

        $shipping_method = ShippingMethod::create($validated);
        $shipping_method->paymentMethods()->sync($request->input('payment_methods', []));
        $shipping_method->countries()->sync($request->input('countries', []));

        return ShippingMethodResource::make($shipping_method);
    }

    public function update(ShippingMethod $shipping_method, Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => ['string', 'max:255'],
            'price' => 'numeric',
            'public' => 'boolean',
            'black_list' => 'boolean',
            'payment_methods' => 'array',
            'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
            'countries' => 'array',
            'countries.*' => ['string', 'size:2', 'exists:countries,code'],
        ]);

        $shipping_method->update($validated);
        $shipping_method->paymentMethods()->sync($request->input('payment_methods', []));
        $shipping_method->countries()->sync($request->input('countries', []));

        return ShippingMethodResource::make($shipping_method);
    }

    public function order(ShippingMethodOrderRequest $request): JsonResponse
    {
        foreach ($request->input('shipping_methods') as $key => $id) {
            ShippingMethod::where('id', $id)->update(['order' => $key]);
        }

        return response()->json(null, 204);
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
