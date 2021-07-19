<?php

namespace App\Services;

use App\Exceptions\StoreException;
use App\Http\Requests\ShippingMethodIndexRequest;
use App\Http\Requests\ShippingMethodOrderRequest;
use App\Http\Requests\ShippingMethodStoreRequest;
use App\Http\Requests\ShippingMethodUpdateRequest;
use App\Http\Resources\ShippingMethodResource;
use App\Models\ShippingMethod;
use App\Services\Contracts\ShippingMethodServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class ShippingMethodService implements ShippingMethodServiceContract
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
                        ->whereDoesntHave(
                            'countries',
                            fn ($query) => $query->where('code', $request->input('country')),
                        );
                });
            });
        }

        $shippingMethods = $query->get();
        $shippingMethods->each(fn ($method) => $method->price = $method->getPrice($request->input('cart_value', 0)));

        return ShippingMethodResource::collection($shippingMethods);
    }

    public function store(ShippingMethodStoreRequest $request): JsonResource
    {
        $shippingMethod = ShippingMethod::create($request->all());
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

    public function update(ShippingMethodUpdateRequest $request, ShippingMethod $shippingMethod): JsonResource
    {
        $shippingMethod->update($request->all());
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

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function destroy(ShippingMethod $shippingMethod): JsonResponse
    {
        if ($shippingMethod->orders()->count() > 0) {
            throw new StoreException(__('admin.error.delete_with_relations'));
        }

        $shippingMethod->delete();

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
