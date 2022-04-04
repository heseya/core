<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductSetParentResource extends Resource
{
    public function base(Request $request): array
    {
        $children = Gate::denies('product_sets.show_hidden')
            ? $this->resource->childrenPublic
            : $this->resource->children;

        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'slug_suffix' => $this->resource->slugSuffix,
            'slug_override' => $this->resource->slugOverride,
            'public' => $this->resource->public,
            'visible' => $this->resource->public_parent && $this->resource->public,
            'hide_on_index' => $this->resource->hide_on_index,
            'parent' => ProductSetResource::make($this->resource->parent),
            'children_ids' => $children->map(
                fn ($child) => $child->getKey(),
            )->toArray(),
            'seo' => SeoMetadataResource::make($this->resource->seo),
            'description_html' => $this->resource->description_html,
            'cover' => MediaResource::make($this->resource->media),
        ];
    }
}
