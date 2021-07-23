<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProductSetResourceSwagger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductSetNestedResource extends Resource implements ProductSetResourceSwagger
{
    public function base(Request $request): array
    {
        $children = !Auth::check() ? $this->children()->public()->get() :
            $this->children;

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'public' => $this->public,
            'public_parent' => $this->public_parent,
            'hide_on_index' => $this->hide_on_index,
            'parent_id' => $this->parent_id,
            'children_ids' => $children->map(
                fn ($child) => $child->getKey(),
            )->toArray(),
            'slug_override' => $this->slugOverride
        ];
    }
}
