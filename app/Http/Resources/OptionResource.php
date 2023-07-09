<?php

namespace App\Http\Resources;

use App\Traits\GetAllTranslations;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class OptionResource extends Resource
{
    use MetadataResource;

    use GetAllTranslations;

    public function base(Request $request): array
    {
        $data = [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'price' => $this->resource->price,
            'disabled' => $this->resource->disabled,
            'available' => $this->resource->available,
            'shipping_time' => $this->resource->shipping_time,
            'shipping_date' => $this->resource->shipping_date,
            'items' => ItemPublicResource::collection($this->resource->items),
        ], $this->metadataResource('options.show_metadata_private'));
        return array_merge(
            $data,
            $request->has('translations') ? $this->getAllTranslations() : []
        );
    }
}
