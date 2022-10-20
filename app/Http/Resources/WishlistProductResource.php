<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class WishlistProductResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'product' => $this->resource->product,
            'created_at' => $this->resource->created_at,
        ];
    }
}
