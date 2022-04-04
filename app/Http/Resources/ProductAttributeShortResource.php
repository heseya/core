<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductAttributeShortResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'selected_options' => AttributeOptionResource::collection($this->resource->pivot->options),
        ];
    }
}
