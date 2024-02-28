<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class CartUnavailableItemResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'cartitem_id' => $this->resource->cartitem_id,
            'quantity' => (float) $this->resource->quantity,
            'error' => CartItemErrorResource::make($this->resource->error),
        ];
    }
}
