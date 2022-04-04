<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class ItemResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'sku' => $this->resource->sku,
            'quantity' => $this->resource->getQuantity($request->input('day')),
        ], $this->metadataResource('items.show_metadata_private'));
    }
}
