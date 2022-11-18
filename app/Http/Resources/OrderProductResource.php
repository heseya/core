<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderProductResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'quantity' => (float) $this->resource->quantity,
            'price' => $this->resource->price,
            'price_initial' => $this->resource->price_initial,
            'vat_rate' => $this->resource->vat_rate,
            'product' => ProductResource::make($this->resource->product),
            'schemas' => OrderSchemaResource::collection($this->resource->schemas),
            'deposits' => DepositResource::collection($this->resource->deposits),
            'discounts' => OrderDiscountResource::collection($this->resource->discounts),
            'shipping_digital' => $this->resource->shipping_digital,
            'is_delivered' => $this->resource->is_delivered,
            'urls' => OrderProductUrlResource::collection($this->resource->urls),
        ];
    }
}
