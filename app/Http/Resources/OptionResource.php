<?php

namespace App\Http\Resources;

use App\Models\Option;
use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

/**
 * @property Option $resource
 */
class OptionResource extends Resource
{
    use GetAllTranslations;
    use MetadataResource;

    public function base(Request $request): array
    {
        $data = [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'price' => $request->header('X-Sales-Channel') ? PriceResource::make($this->resource->getMappedPriceForSalesChannel($request->header('X-Sales-Channel'))) : null,
            'prices' => PriceResource::collection($this->resource->mapPrices),
            'available' => $this->resource->available,
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'items' => ItemPublicResource::collection($this->resource->items),
        ];

        return array_merge(
            $data,
            $request->boolean('with_translations') ? $this->getAllTranslations('options.show_hidden') : [],
            $this->metadataResource('options.show_metadata_private'),
        );
    }
}
