<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProductSetResourceSwagger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductSetResource extends Resource implements ProductSetResourceSwagger
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
            'parent' => ProductSetNestedResource::make($this->parent),
            'children' => ProductSetNestedResource::collection($children),
            'slug_override' => $this->slugOverride
        ];
    }
}
