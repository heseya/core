<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;

class ProductAttributeResource extends ProductAttributeShortResource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge(
            parent::base($request),
            [
                'id' => $this->resource->getKey(),
                'slug' => $this->resource->slug,
                'description' => $this->resource->description,
                'type' => $this->resource->type->value,
                'global' => $this->resource->global,
                'sortable' => $this->resource->sortable,
            ],
            $this->metadataResource('products.show_metadata_private'),
        );
    }
}
