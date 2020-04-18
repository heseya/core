<?php

namespace App\Http\Resources;

use App\Http\Resources\ProductResource;
use App\Http\Resources\SchemaItemResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'qty' => $this->qty,
            'price' => $this->price,
            'product' => new ProductResource($this->product),
            'schema_items' => SchemaItemResource::collection($this->schemaItems),
        ];
    }
}
