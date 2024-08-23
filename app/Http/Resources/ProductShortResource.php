<?php

namespace App\Http\Resources;

use App\Models\Product;
use Domain\Price\Dtos\ProductCachedPriceDto;
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
            'initial_price' => $request->header('X-Sales-Channel') ? ProductCachedPriceDto::from($this->resource->getCachedInitialPriceForSalesChannel($request->header('X-Sales-Channel'))) : null,
            'price' => $request->header('X-Sales-Channel') ? ProductCachedPriceDto::from($this->resource->getCachedMinPriceForSalesChannel($request->header('X-Sales-Channel'))) : null,
            'public' => $this->resource->public,
            'visible' => $this->resource->public,
            'available' => $this->resource->available,
            'cover' => MediaResource::make($this->resource->media->first()),
            'quantity' => $this->resource->quantity,
        ];
    }
}
