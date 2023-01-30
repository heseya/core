<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderDiscountResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'discount' => $this->resource->code !== null
                ? CouponResource::make($this)->baseOnly()
                : SaleResource::make($this)->baseOnly(),
            'name' => $this->resource->pivot->name,
            'code' => $this->resource->pivot->code,
            'type' => $this->resource->pivot->type,
            'target_type' => $this->resource->pivot->target_type,
            'value' => $this->resource->pivot->value,
            'applied_discount' => $this->resource->pivot->applied_discount,
        ];
    }
}
