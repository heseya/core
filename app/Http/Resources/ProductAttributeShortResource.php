<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductAttributeShortResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'name' => $this->name,
            'selected_options' => AttributeOptionResource::collection($this->pivot->options),
        ];
    }
}
