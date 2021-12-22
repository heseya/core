<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductSetResource extends Resource
{
    public function base(Request $request): array
    {
        $children = Gate::denies('product_sets.show_hidden')
            ? $this->childrenPublic
            : $this->children;

        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'slug' => $this->slug,
            'slug_suffix' => $this->slugSuffix,
            'slug_override' => $this->slugOverride,
            'public' => $this->public,
            'visible' => $this->public_parent && $this->public,
            'hide_on_index' => $this->hide_on_index,
            'parent_id' => $this->parent_id,
            'children_ids' => $children->map(
                fn ($child) => $child->getKey(),
            )->toArray(),
            'cover' => MediaResource::make($this->media),
        ];
    }
}
