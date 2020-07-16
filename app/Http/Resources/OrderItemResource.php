<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderItemResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function base(Request $request): array
    {
        return [
            'quantity' => $this->quantity,
            'price' => $this->price,
            'product' => ProductResource::make($this->product),
            'schema_items' => SchemaItemResource::collection($this->schemaItems),
        ];
    }
}
