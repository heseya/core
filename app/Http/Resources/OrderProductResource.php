<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderProductResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'quantity' => $this->resource->quantity,
            'price' => $this->resource->price,
            'product' => ProductResource::make($this->resource->product),
            'schemas' => OrderSchemaResource::collection($this->resource->schemas),
            'deposits' => DepositResource::collection($this->resource->deposits),
        ];
    }
}
