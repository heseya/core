<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProductSetResourceSwagger;
use App\Models\ProductSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// Universal class but cant work because resources are broken
class ProductSetResourceTree extends Resource implements ProductSetResourceSwagger
{
    public function base(Request $request): array
    {
        $parent = $this->nested ? ['parent_id' => $this->parent_id] : [
            'parent' => ProductSetResource::make($this->parent, true),
        ];

        $children = Auth::check() ? $this->children()->private()->get() :
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
            'public' => $this->public,
            'hide_on_index' => $this->hide_on_index,
            'parent' => ProductSetResourceNested::make($this->parent, true),
            'children' => ProductSetResourceNestedTree::collection($children),
            'slug_override' => $this->slugOverride
        ];
    }
}
