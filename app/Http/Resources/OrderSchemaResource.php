<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class OrderSchemaResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'value' => $this->resource->value,
            'price' => PriceResource::make($this->resource->price),
            'price_initial' => PriceResource::make($this->resource->price_initial),
        ];
    }
}
