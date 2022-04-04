<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductAttributeResource extends ProductAttributeShortResource
{
    public function base(Request $request): array
    {
        return array_merge(
            parent::base($request),
            [
                'id' => $this->resource->getKey(),
                'slug' => $this->resource->slug,
                'description' => $this->resource->description,
                'type' => $this->resource->type->value,
                'global' => $this->resource->global,
                'sortable' => $this->resource->sortable,
            ]
        );
    }
}
