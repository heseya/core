<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class OptionResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'price' => $this->resource->price,
            'disabled' => $this->resource->disabled,
            'available' => $this->resource->available,
            'items' => ItemPublicResource::collection($this->resource->items),
        ], $this->metadataResource('options.show_metadata_private'));
    }
}
