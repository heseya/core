<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProductSetResourceSwagger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductSetParentResource extends Resource implements ProductSetResourceSwagger
{
    public function base(Request $request): array
    {
        $children = !Auth::check() ? $this->children()->public()->get() :
            $this->children;

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'slug_suffix' => $this->slugSuffix,
            'slug_override' => $this->slugOverride,
            'public' => $this->public,
            'visible' => $this->public_parent && $this->public,
            'hide_on_index' => $this->hide_on_index,
            'parent' => ProductSetResource::make($this->parent),
            'children_ids' => $children->map(
                fn ($child) => $child->getKey(),
            )->toArray(),
        ];
    }
}
