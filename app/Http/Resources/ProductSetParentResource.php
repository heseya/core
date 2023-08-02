<?php

namespace App\Http\Resources;

use App\Traits\MetadataResource;
use Domain\ProductAttribute\Resources\AttributeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductSetParentResource extends Resource
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
            'parent' => ProductSetResource::make($this->resource->parent),
            'children_ids' => $children->map(fn ($child) => $child->getKey()),
            'seo' => SeoMetadataResource::make($this->resource->seo),
            'description_html' => $this->resource->description_html,
            'cover' => MediaResource::make($this->resource->media),
            'attributes' => AttributeResource::collection($this->resource->attributes),
        ], $this->metadataResource('product_sets.show_metadata_private'));
    }
}
