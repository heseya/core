<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CartItemResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'cartitem_id' => $this->resource->cartitem_id,
            'price' => $this->resource->price,
            'price_discounted' => $this->resource->price_discounted,
            'quantity' => $this->resource->quantity,
        ];
    }
}
