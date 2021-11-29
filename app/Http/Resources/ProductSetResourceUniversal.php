<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProductSetResourceSwagger;
use Illuminate\Http\Request;

// Universal class can't work because methods are not coppied to collections
class ProductSetResourceUniversal extends Resource implements ProductSetResourceSwagger
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
            'parent' => ProductSetResourceUniversal::make($this->parent)
                ->setIsPublic($this->public),
        ] : ['parent_id' => $this->parent_id];

        $children = $this->public
            ? $this->childrenPublic
            : $this->children;

        $childrenResource = $this->showChildren ? [
            'children' => ProductSetResourceUniversal::collection($children)
                ->setIsPublic($this->public),
        ] : [
            'children_ids' => $children->map(
                fn ($child) => $child->getKey(),
            )->toArray(),
        ];

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'slug_suffix' => $this->slugSuffix,
            'slug_override' => $this->slugOverride,
            'public' => $this->public,
            'visible' => $this->public_parent && $this->public,
            'hide_on_index' => $this->hide_on_index,
            'seo' => SeoMetadataResource::make($this->seo),
        ] + $parentResource + $childrenResource;
    }
}
