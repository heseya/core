<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class FavouriteProductSetResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'product_set' => ProductSetResource::make($this->resource->productSet),
            'created_at' => $this->resource->created_at,
        ];
    }
}
