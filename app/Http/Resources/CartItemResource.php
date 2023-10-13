<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CartItemResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'cartitem_id' => $this->resource->cartitem_id,
            'price' => PriceResource::make($this->resource->price),
            'price_discounted' => PriceResource::make($this->resource->price_discounted),
            'quantity' => (float) $this->resource->quantity,
        ];
    }
}
