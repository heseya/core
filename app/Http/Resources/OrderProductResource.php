<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderProductResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'quantity' => $this->quantity,
            'price' => $this->price,
            'product' => ProductResource::make($this->product),
            'schemas' => OrderSchemaResource::collection($this->schemas),
        ];
    }
}
