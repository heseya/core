<?php

namespace App\Http\Resources;

use App\Models\Item;
use Illuminate\Http\Request;

/**
 * @property Item $resource
 */
class ProductItemResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'sku' => $this->resource->sku,
            'required_quantity' => $this->resource->pivot->required_quantity,
        ];
    }
}
