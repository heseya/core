<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductSetParentChildrenResource extends Resource
{
    use MetadataResource;

    public function base(Request $request): array
    {
        $children = Gate::denies('product_sets.show_hidden')
            ? $this->resource->childrenPublic
            : $this->resource->children;

        return array_merge([
            'id' => $this->resource->getKey(),
            'slug' => $this->resource->slug,
            'name' => $this->resource->name,
            'slug_suffix' => $this->resource->slugSuffix,
            'slug_override' => $this->resource->slugOverride,
            'public' => $this->resource->public,
            'visible' => $this->resource->public_parent && $this->resource->public,
            'hide_on_index' => $this->resource->hide_on_index,
            'parent' => ProductSetResource::make($this->resource->parent),
            'children' => ProductSetChildrenResource::collection($children),
            'seo' => SeoMetadataResource::make($this->resource->seo),
            'description_html' => $this->resource->description_html,
            'cover' => MediaResource::make($this->resource->media),
        ], $this->metadataResource('product_sets.show_metadata_private'));
    }
}
