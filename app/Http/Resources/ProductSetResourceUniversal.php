<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

// Universal class can't work because methods are not coppied to collections
class ProductSetResourceUniversal extends Resource
{
    private bool $showParent = false;
    private bool $showChildren = false;
    private bool $public = false;

//    public function withParent(bool $showParent = true): self
//    {
//        $this->showParent = $showParent;
//
//        return $this;
//    }
//
//    public function withChildren(bool $showChildren = true): self
//    {
//        $this->showChildren = $showChildren;
//
//        return $this;
//    }
//
//    public function setIsPublic(bool $public): self
//    {
//        $this->public = $public;
//
//        return $this;
//    }

    public function base(Request $request): array
    {
        $parentResource = $this->showParent ? [
            'parent' => ProductSetResourceUniversal::make($this->resource->parent)->setIsPublic($this->public),
        ] : ['parent_id' => $this->resource->parent_id];

        $children = $this->public
            ? $this->resource->childrenPublic
            : $this->resource->children;

        $childrenResource = $this->showChildren ? [
            'children' => ProductSetResourceUniversal::collection($children)->setIsPublic($this->public),
        ] : [
            'children_ids' => $children->map(fn ($child) => $child->getKey()),
        ];

        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'slug_suffix' => $this->resource->slugSuffix,
            'slug_override' => $this->resource->slugOverride,
            'public' => $this->resource->public,
            'visible' => $this->resource->public_parent && $this->resource->public,
            'hide_on_index' => $this->resource->hide_on_index,
            'seo' => SeoMetadataResource::make($this->resource->seo),
            'description_html' => $this->resource->description_html,
        ] + $parentResource + $childrenResource;
    }
}
