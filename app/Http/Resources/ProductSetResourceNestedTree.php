<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProductSetResourceSwagger;
use App\Models\ProductSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// Universal class but cant work because resources are broken
class ProductSetResourceNestedTree extends Resource implements ProductSetResourceSwagger
{
    public function base(Request $request): array
    {
        $children = Auth::check() ? $this->children()->private()->get() :
            $this->children;

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'public' => $this->public,
            'hide_on_index' => $this->hide_on_index,
            'parent_id' => $this->parent_id,
            'children' => ProductSetResourceNestedTree::collection($children),
            'slug_override' => $this->slugOverride
        ];
    }
}
