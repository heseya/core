<?php

namespace App\Http\Controllers;

use App\Exceptions\StoreException;
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
use Illuminate\Support\Facades\Response;

class ShippingMethodController extends Controller implements ShippingMethodControllerSwagger
{
    public function index(ShippingMethodIndexRequest $request): JsonResource
    {
        $query = ShippingMethod::query()->orderBy('order');

        if (Auth::check()) {
            $query->with('paymentMethods');
        } else {
            $query
                ->with(['paymentMethods' => fn ($query) => $query->where('public', true)])
                ->where('public', true);
        }

        if ($request->has('country')) {
            $query->where(function (Builder $query) use ($request) {
                $query->where(function (Builder $query) use ($request) {
                    $query
                        ->where('black_list', false)
                        ->whereHas('countries', fn ($query) => $query->where('code', $request->input('country')));
                })->orWhere(function (Builder $query) use ($request) {
                    $query
                        ->where('black_list', true)
                        ->whereDoesntHave('countries', fn ($query) => $query->where('code', $request->input('country')));
                });
            });
        }

        $shippingMethods = $query->get();
        $shippingMethods->each(fn ($method) => $method->price = $method->getPrice($request->input('cart_value', 0)));

        return ShippingMethodResource::collection($shippingMethods);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'public' => 'boolean',
            'black_list' => 'boolean',
            'payment_methods' => 'array',
            'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
            'countries' => 'array',
            'countries.*' => ['string', 'size:2', 'exists:countries,code'],
            'price_ranges' => ['required', 'array', 'min:1'],
            'price_ranges.*.start' => ['required', 'numeric', 'min:0'],
            'price_ranges.*.value' => ['required', 'numeric', 'min:0'],
        ]);

        $shippingMethod = ShippingMethod::create($validated);
        $shippingMethod->paymentMethods()->sync($request->input('payment_methods', []));
        $shippingMethod->countries()->sync($request->input('countries', []));

        foreach ($request->input('price_ranges') as $range) {
            $priceRange = $shippingMethod->priceRanges()->firstOrCreate([
                'start' => $range['start'],
            ]);
            $priceRange->prices()->create([
                'value' => $range['value'],
            ]);
        }

        return ShippingMethodResource::make($shippingMethod);
    }

    public function update(ShippingMethod $shippingMethod, Request $request): JsonResource
    {
        $validated = $request->validate([
            'name' => ['string', 'max:255'],
            'public' => 'boolean',
            'black_list' => 'boolean',
            'payment_methods' => 'array',
            'payment_methods.*' => ['uuid', 'exists:payment_methods,id'],
            'countries' => 'array',
            'countries.*' => ['string', 'size:2', 'exists:countries,code'],
            'price_ranges' => ['array', 'min:1'],
            'price_ranges.*.start' => ['required', 'numeric', 'min:0'],
            'price_ranges.*.value' => ['required', 'numeric', 'min:0'],
        ]);

        $shippingMethod->update($validated);
        $shippingMethod->paymentMethods()->sync($request->input('payment_methods', []));
        $shippingMethod->countries()->sync($request->input('countries', []));

        if ($request->has('price_ranges')) {
            $shippingMethod->priceRanges()->delete();

            foreach ($request->input('price_ranges') as $range) {
                $priceRange = $shippingMethod->priceRanges()->firstOrCreate([
                    'start' => $range['start'],
                ]);
                $priceRange->prices()->create([
                    'value' => $range['value'],
                ]);
            }
        }

        return ShippingMethodResource::make($shippingMethod);
    }

    public function order(ShippingMethodOrderRequest $request): JsonResponse
    {
        foreach ($request->input('shipping_methods') as $key => $id) {
            ShippingMethod::where('id', $id)->update(['order' => $key]);
        }

        return Response::json(null, 204);
    }

    public function destroy(ShippingMethod $shipping_method): JsonResponse
    {
        if ($shipping_method->orders()->count() > 0) {
            throw new StoreException(__('admin.error.delete_with_relations'));
        }

        $shipping_method->delete();

        return Response::json(null, 204);
    }
}
