<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProductSetResourceSwagger;
use App\Http\Resources\Swagger\ProductSetTreeResourceSwagger;
use App\Models\ProductSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// Universal class but cant work because resources are broken
class ProductSetResourceUniversal extends Resource implements ProductSetResourceSwagger, ProductSetTreeResourceSwagger
{
    private $nested;
    private $tree;

    public function __construct(ProductSet $resource, $nested = false, $tree = false)
    {
        parent::__construct($resource);

        $this->nested = $nested;
        $this->tree = $tree;
    }

    public function base(Request $request): array
    {
        $parent = $this->nested ? ['parent_id' => $this->parent_id] : [
            'parent' => ProductSetResource::make($this->parent, true),
        ];

        $children = !Auth::check() ? $this->children()->public()->get() :
            $this->children;

        $childrenResource = $this->tree ? [
            'children' => ProductSetResource::collection($children),
        ] : [
            'children_ids' => $children->map(
                fn ($child) => $child->getKey(),
            )->toArray(),
        ];

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'slug_override' => Str::startsWith($this->slug, $this->parent->slug . '-'),
            'public' => $this->public,
            'public_parent' => $this->public_parent,
            'hide_on_index' => $this->hide_on_index,
        ] + $parent + $childrenResource;
    }
}
