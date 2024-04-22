<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Domain\ProductAttribute\Models\Attribute;
use Illuminate\Http\Request;

/**
 * @property Attribute $resource
 */
class AttributeResource extends AttributeShortResource
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
                'include_in_text_search' => $this->resource->include_in_text_search,
                'match_any' => $this->resource->match_any,
            ],
            $this->metadataResource('products.show_metadata_private'),
        );
    }
}
