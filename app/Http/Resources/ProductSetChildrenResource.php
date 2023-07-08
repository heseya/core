<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductSetChildrenResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        $children = Gate::denies('product_sets.show_hidden')
            ? $this->resource->allChildrenPublic
            : $this->resource->allChildren;

        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'slug_suffix' => $this->resource->slugSuffix,
            'slug_override' => $this->resource->slugOverride,
            'public' => $this->resource->public,
            'visible' => $this->resource->public_parent && $this->resource->public,
            'parent_id' => $this->resource->parent_id,
            'children' => self::collection($children),
            'cover' => MediaResource::make($this->resource->media),
        ], $this->metadataResource('product_sets.show_metadata_private'));
    }

    public function view(Request $request): array
    {
        return [
            'attributes' => AttributeResource::collection($this->resource->attributes),
        ];
    }
}
