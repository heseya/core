<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class ProductAttributeOptionShortResource extends Resource
{
    public function base(Request $request): array
    {
        return [
            'name' => $this->attribute->name,
            'selected_option' => AttributeOptionResource::make($this),
        ];
    }
}
