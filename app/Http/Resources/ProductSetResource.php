<?php

namespace App\Http\Resources;

use App\Http\Resources\Swagger\ProductSetResourceSwagger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductSetResource extends Resource implements ProductSetResourceSwagger
{
    public function base(Request $request): array
    {
        $children = Auth::check() ? $this->children()->private()->get() :
            $this->children;

        $childrenIds = $children->map(
            fn ($child) => $child->getKey(),
        )->toArray();

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'public' => $this->public,
            'hide_on_index' => $this->hide_on_index,
            'parent_id' => $this->parent_id,
            'children_ids' => $childrenIds,
        ];
    }
}
