<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CartResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'currency' => $this->resource->summary->getCurrency()->getCurrencyCode(),
            'cart_total_initial' => PriceResource::make($this->resource->cart_total_initial),
            'cart_total' => PriceResource::make($this->resource->cart_total),
            'shipping_price_initial' => PriceResource::make($this->resource->shipping_price_initial),
            'shipping_price' => PriceResource::make($this->resource->shipping_price),
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'summary' => PriceResource::make($this->resource->summary),
            'items' => CartItemResource::collection($this->resource->items),
            'coupons' => CouponShortResource::collection($this->resource->coupons),
            'sales' => SalesShortResource::collection($this->resource->sales),
            'unavailable_items' => CartUnavailableItemResource::collection($this->resource->unavailable_items),
        ];
    }
}
