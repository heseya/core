<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductPriceResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'price_min' => $this->resource->price_min,
            'price_max' => $this->resource->price_max,
        ];
    }
}
