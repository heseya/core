<?php

namespace App\Http\Resources;

use App\Http\Resources\ProductResource;
use App\Http\Resources\SchemaItemResource;

class OrderItemResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function base($request): array
    {
        return [
            'quantity' => $this->quantity,
            'price' => $this->price,
            'product' => ProductResource::make($this->product),
            'schema_items' => SchemaItemResource::collection($this->schemaItems),
        ];
    }
}
