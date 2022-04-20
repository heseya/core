<?php

namespace App\Services;

use App\Dtos\ShippingMethodDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Exceptions\StoreException;
use App\Models\ShippingMethod;
use App\Services\Contracts\ShippingMethodServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ShippingMethodService implements ShippingMethodServiceContract
{
    public function index(?array $search, ?string $country, float $cartValue): Collection
    {
        $query = ShippingMethod::query()
            ->searchByCriteria($search)
            ->with('metadata')
            ->orderBy('order');

        if (!Auth::user()->can('shipping_methods.show_hidden')) {
            $query->where('public', true);
        }

        if (Auth::user()->hasAnyPermission([
            'payment_methods.show_hidden',
            'shipping_methods.edit',
        ])) {
            $query->with('paymentMethods');
        } else {
            $query->with([
                'paymentMethods' => fn ($query) => $query->where('public', true),
            ]);
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
        $shippingMethods->each(fn (ShippingMethod $method) => $method->price = $method->getPrice($cartValue));

        return $shippingMethods;
    }

    public function store(ShippingMethodDto $shippingMethodDto): ShippingMethod
    {
        $attributes = array_merge(
            $shippingMethodDto->toArray(),
            ['order' => ShippingMethod::count()],
        );

        $shippingMethod = ShippingMethod::create($attributes);

        if ($shippingMethodDto->getPaymentMethods() !== null) {
            $shippingMethod->paymentMethods()->sync($shippingMethodDto->getPaymentMethods());
        }

        if ($shippingMethodDto->getCountries() !== null) {
            $shippingMethod->countries()->sync($shippingMethodDto->getCountries());
        }

        foreach ($shippingMethodDto->getPriceRanges() as $range) {
            $priceRange = $shippingMethod->priceRanges()->firstOrCreate([
                'start' => $range['start'],
            ]);
            $priceRange->prices()->create([
                'value' => $range['value'],
            ]);
        }

        return $shippingMethod;
    }

    public function update(ShippingMethod $shippingMethod, ShippingMethodDto $shippingMethodDto): ShippingMethod
    {
        $shippingMethod->update($shippingMethodDto->toArray());

        if ($shippingMethodDto->getPaymentMethods() !== null) {
            $shippingMethod->paymentMethods()->sync($shippingMethodDto->getPaymentMethods());
        }

        if ($shippingMethodDto->getCountries() !== null) {
            $shippingMethod->countries()->sync($shippingMethodDto->getCountries());
        }

        if ($shippingMethodDto->getPriceRanges() !== null) {
            $shippingMethod->priceRanges()->delete();

            foreach ($shippingMethodDto->getPriceRanges() as $range) {
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
            throw new ClientException(Exceptions::CLIENT_DELETE_WHEN_RELATION_EXISTS);
        }

        $shippingMethod->delete();
    }
}
