<?php

namespace App\Http\Resources;

use Domain\ProductAttribute\Resources\AttributeOptionResource;
use Illuminate\Http\Request;

class ProductAttributeShortResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'selected_options' => AttributeOptionResource::collection(
                $this->resource->pivot->options ?? $this->resource->options,
            ),
        ];
    }
}
