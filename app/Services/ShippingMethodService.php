<?php

namespace App\Services;

use App\Exceptions\StoreException;
use App\Http\Requests\ShippingMethodStoreRequest;
use App\Http\Requests\ShippingMethodUpdateRequest;
use App\Models\ShippingMethod;
use App\Services\Contracts\ShippingMethodServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ShippingMethodService implements ShippingMethodServiceContract
{
    public function index(?string $country, float $cartValue): Collection
    {
        $query = ShippingMethod::query()->orderBy('order');
        if (Auth::check()) {
            $query->with('paymentMethods');
        } else {
            $query
                ->with(['paymentMethods' => fn ($query) => $query->where('public', true)])
                ->where('public', true);
        }

        if ($country) {
            $query->where(function (Builder $query) use ($country): void {
                $query->where(function (Builder $query) use ($country): void {
                    $query
                        ->where('black_list', false)
                        ->whereHas('countries', fn ($query) => $query->where('code', $country));
                })->orWhere(function (Builder $query) use ($country): void {
                    $query
                        ->where('black_list', true)
                        ->whereDoesntHave(
                            'countries',
                            fn ($query) => $query->where('code', $country),
                        );
                });
            });
        }

        $shippingMethods = $query->get();
        $shippingMethods->each(fn ($method) => $method->price = $method->getPrice($cartValue));

        return $shippingMethods;
    }

    public function store(ShippingMethodStoreRequest $request): ShippingMethod
    {
        $attributes = $request->validated();
        $shippingMethodOrderLast = ShippingMethod::orderBy('order', 'desc')->first()->order ?? 0;
        $attributes = $attributes + ['order' => $shippingMethodOrderLast + 1];

        $shippingMethod = ShippingMethod::create($attributes);
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

        return $shippingMethod;
    }

    public function update(ShippingMethodUpdateRequest $request, ShippingMethod $shippingMethod): ShippingMethod
    {
        $shippingMethod->update($request->validated());
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

        return $shippingMethod;
    }

    public function reorder(array $shippingMethods): void
    {
        foreach ($shippingMethods as $key => $id) {
            ShippingMethod::where('id', $id)->update(['order' => $key]);
        }
    }

    public function destroy(ShippingMethod $shippingMethod): void
    {
        if ($shippingMethod->orders()->count() > 0) {
            throw new StoreException(__('admin.error.delete_with_relations'));
        }

        $shippingMethod->delete();
    }
}
