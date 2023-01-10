<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductSetResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        $children = Gate::denies('product_sets.show_hidden')
            ? $this->resource->childrenPublic
            : $this->resource->children;

        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'slug_suffix' => $this->resource->slugSuffix,
            'slug_override' => $this->resource->slugOverride,
            'public' => $this->resource->public,
            'visible' => $this->resource->public_parent && $this->resource->public,
            'parent_id' => $this->resource->parent_id,
            'children_ids' => $children->map(fn ($child) => $child->getKey())->toArray(),
            'cover' => MediaResource::make($this->resource->media),
        ], $this->metadataResource('product_sets.show_metadata_private'));
    }
}
