<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductAvailabilityResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'quantity' => $this->resource->quantity,
        ];
    }
}
