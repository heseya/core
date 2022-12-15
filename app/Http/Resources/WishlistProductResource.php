<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WishlistProductResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'product' => ProductResource::make($this->resource->product)->isIndex(),
            'created_at' => $this->resource->created_at,
        ];
    }
}
