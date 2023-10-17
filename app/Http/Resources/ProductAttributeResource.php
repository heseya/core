<?php

namespace App\Http\Resources;

use App\Models\ProductAttribute;
use App\Traits\MetadataResource;
use Illuminate\Http\Request;

/**
 * @property ProductAttribute $resource
 */
class ProductAttributeResource extends ProductAttributeShortResource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        return array_merge(
            parent::base($request),
            [
                'id' => $this->resource->attribute?->getKey(),
                'slug' => $this->resource->attribute?->slug,
                'description' => $this->resource->attribute?->description,
                'type' => $this->resource->attribute?->type->value,
                'global' => $this->resource->attribute?->global,
                'sortable' => $this->resource->attribute?->sortable,
            ],
            $this->resource->attribute ? $this->metadataResource('products.show_metadata_private', $this->resource->attribute) : [],
        );
    }
}
