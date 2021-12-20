<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ShippingMethodResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'price' => $this->price,
            'public' => $this->public,
            'black_list' => $this->black_list,
            'payment_methods' => PaymentMethodResource::collection($this->paymentMethods),
            'countries' => CountryResource::collection($this->countries),
            'price_ranges' => PriceRangeResource::collection($this->priceRanges->sortBy('start')),
            'shipping_time' => $this->shipping_time,
        ];
    }
}
