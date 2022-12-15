<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductShortResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'price' => $this->resource->price,
            'price_min' => $this->resource->price_min ?? $this->resource->price_min_initial,
            'price_max' => $this->resource->price_max ?? $this->resource->price_max_initial,
            'public' => $this->resource->public,
            'visible' => $this->resource->public,
            'available' => $this->resource->available,
            'cover' => MediaResource::make($this->resource->media->first()),
            'quantity' => $this->resource->quantity,
        ];
    }
}
