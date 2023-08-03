<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * @property Product $resource
 */
class ProductShortResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'prices_base' => PriceResource::collection($this->resource->pricesBase),
            'prices_min' => PriceResource::collection($this->resource->pricesMin ?? $this->resource->pricesMinInitial),
            'prices_max' => PriceResource::collection($this->resource->pricesMax ?? $this->resource->pricesMaxInitial),
            'public' => $this->resource->public,
            'visible' => $this->resource->public,
            'available' => $this->resource->available,
            'cover' => MediaResource::make($this->resource->media->first()),
            'quantity' => $this->resource->quantity,
        ];
    }
}
