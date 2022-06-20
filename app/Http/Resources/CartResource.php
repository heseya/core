<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CartResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'cart_total_initial' => round($this->resource->cart_total_initial, 2, PHP_ROUND_HALF_UP),
            'cart_total' => round($this->resource->cart_total, 2, PHP_ROUND_HALF_UP),
            'shipping_price_initial' => round($this->resource->shipping_price_initial, 2, PHP_ROUND_HALF_UP),
            'shipping_price' => round($this->resource->shipping_price, 2, PHP_ROUND_HALF_UP),
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'summary' => round($this->resource->summary, 2, PHP_ROUND_HALF_UP),
            'items' => CartItemResource::collection($this->resource->items),
            'coupons' => CouponShortResource::collection($this->resource->coupons),
            'sales' => SalesShortResource::collection($this->resource->sales),
        ];
    }
}
