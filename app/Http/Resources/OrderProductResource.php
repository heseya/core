<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderProductResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'quantity' => $this->resource->quantity,
            'price' => $this->resource->price,
            'price_initial' => $this->resource->price_initial,
            'product' => ProductResource::make($this->resource->product),
            'schemas' => OrderSchemaResource::collection($this->resource->schemas),
            'deposits' => DepositResource::collection($this->resource->deposits),
            'discounts' => OrderDiscountResource::collection($this->resource->discounts),
        ];
    }
}
