<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderProductResourcePublic extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'order_id' => $this->resource->order_id,
            'name' => $this->resource->name,
            'quantity' => (float) $this->resource->quantity,
            'currency' => $this->resource->currency,
            'price' => PriceResource::make($this->resource->price),
            'price_initial' => PriceResource::make($this->resource->price_initial),
            'vat_rate' => $this->resource->vat_rate,
            'product' => ProductResource::make($this->resource->product),
            'schemas' => OrderSchemaResource::collection($this->resource->schemas),
            'shipping_digital' => $this->resource->shipping_digital,
            'urls' => OrderProductUrlResource::collection($this->resource->urls),
        ];
    }
}
