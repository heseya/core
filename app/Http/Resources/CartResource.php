<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CartResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'currency' => $this->resource->summary->getCurrency()->getCurrencyCode(),
            'cart_total_initial' => $this->resource->cart_total_initial->getAmount(),
            'cart_total' => $this->resource->cart_total->getAmount(),
            'shipping_price_initial' => $this->resource->shipping_price_initial->getAmount(),
            'shipping_price' => $this->resource->shipping_price->getAmount(),
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'summary' => $this->resource->summary->getAmount(),
            'items' => CartItemResource::collection($this->resource->items),
            'coupons' => CouponShortResource::collection($this->resource->coupons),
            'sales' => SalesShortResource::collection($this->resource->sales),
        ];
    }
}
