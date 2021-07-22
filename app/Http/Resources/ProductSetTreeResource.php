<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProductSetTreeResourceSwagger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductSetTreeResource extends Resource implements ProductSetTreeResourceSwagger
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
            'hide_on_index' => $this->hide_on_index,
            'parent' => ProductSetNestedResource::make($this->parent, true),
            'children' => ProductSetNestedTreeResource::collection($children),
            'slug_override' => $this->slugOverride
        ];
    }
}
