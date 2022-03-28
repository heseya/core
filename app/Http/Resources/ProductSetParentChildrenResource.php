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
            ? $this->childrenPublic
            : $this->children;

        return array_merge([
            'id' => $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'slug_suffix' => $this->slugSuffix,
            'slug_override' => $this->slugOverride,
            'public' => $this->public,
            'visible' => $this->public_parent && $this->public,
            'hide_on_index' => $this->hide_on_index,
            'parent' => ProductSetResource::make($this->parent),
            'children' => ProductSetChildrenResource::collection($children),
            'seo' => SeoMetadataResource::make($this->seo),
            'description_html' => $this->description_html,
            'cover' => MediaResource::make($this->media),
            'attributes' => AttributeResource::collection($this->attributes),
        ], $this->metadataResource('product_sets.show_metadata_private'));
    }
}
