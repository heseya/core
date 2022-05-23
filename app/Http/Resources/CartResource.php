<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CartResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'cart_total_initial' => $this->resource->cart_total_initial,
            'cart_total' => $this->resource->cart_total,
            'shipping_price_initial' => $this->resource->shipping_price_initial,
            'shipping_price' => $this->resource->shipping_price,
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'summary' => $this->resource->summary,
            'items' => CartItemResource::collection($this->resource->items),
            'coupons' => CouponShortResource::collection($this->resource->coupons),
            'sales' => SalesShortResource::collection($this->resource->sales),
        ];
    }
}
