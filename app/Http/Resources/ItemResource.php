<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ItemResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'sku' => $this->resource->sku,
            'quantity' => $this->resource->getQuantity($request->input('day')),
        ];
    }
}
